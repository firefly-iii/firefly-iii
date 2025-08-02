<?php

/**
 * AccountTransformer.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Transformers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class AccountTransformer
 */
class AccountTransformer extends AbstractTransformer
{
    protected bool                       $convertToPrimary;
    protected TransactionCurrency        $primary;
    protected AccountRepositoryInterface $repository;

    /**
     * AccountTransformer constructor.
     */
    public function __construct()
    {
        $this->parameters       = new ParameterBag();
        $this->repository       = app(AccountRepositoryInterface::class);
        $this->convertToPrimary = Amount::convertToPrimary();
        $this->primary          = Amount::getPrimaryCurrency();
    }

    /**
     * Transform the account.
     *
     * @throws FireflyException
     */
    public function transform(Account $account): array
    {
        if (null === $account->meta) {
            $account->meta = [];
        }

        // get account type:
        $accountType         = (string)config(sprintf('firefly.shortNamesByFullName.%s', $account->full_account_type));
        $liabilityType       = (string)config(sprintf('firefly.shortLiabilityNameByFullName.%s', $account->full_account_type));
        $liabilityType       = '' === $liabilityType ? null : strtolower($liabilityType);
        $liabilityDirection  = $account->meta['liability_direction'] ?? null;
        $accountRole         = $this->getAccountRole($account, $accountType);
        $hasCurrencySettings = array_key_exists('currency_id', $account->meta) && (int)$account->meta['currency_id'] > 0;
        $includeNetWorth     = 1 === (int)($account->meta['include_net_worth'] ?? 0);
        $longitude           = $account->meta['location']['longitude'] ?? null;
        $latitude            = $account->meta['location']['latitude'] ?? null;
        $zoomLevel           = $account->meta['location']['zoom_level'] ?? null;
        $order               = $account->order;

        // date (for balance etc.)
        $date = $this->getDate();
        $date->endOfDay();

        // no order for some accounts:
        if (!in_array(strtolower($accountType), ['liability', 'liabilities', 'asset'], true)) {
            $order = null;
        }

        // get some listed information from the account meta-data:
        [$creditCardType, $monthlyPaymentDate] = $this->getCCInfo($account, $accountRole, $accountType);
        [$openingBalance, $openingBalanceDate] = $this->getOpeningBalance($account, $accountType);
        [$interest, $interestPeriod] = $this->getInterest($account, $accountType);

        // get currencies:
        $currency = $this->primary; // assume primary currency
        if ($hasCurrencySettings) {
            $currency = $account->meta['currency'];
        }

        // get the current balance:
        $finalBalance = Steam::finalAccountBalance($account, $date, $this->primary, $this->convertToPrimary);
        Log::debug(sprintf('Call finalAccountBalance(%s) with date/time "%s"', var_export($this->convertToPrimary, true), $date->toIso8601String()), $finalBalance);

        // set some pc_ default values to NULL:
        $pcCurrentBalance = null;
        $pcOpeningBalance = null;
        $pcVirtualBalance = null;
        $pcDebtAmount     = null;

        // collect current balances:
        $currentBalance = Steam::bcround($finalBalance[$currency->code] ?? '0', $currency->decimal_places);
        $openingBalance = Steam::bcround($openingBalance ?? '0', $currency->decimal_places);
        $virtualBalance = Steam::bcround($account->virtual_balance ?? '0', $currency->decimal_places);
        $debtAmount     = $account->meta['current_debt'] ?? null;

        // convert to primary currency if needed:
        if ($this->convertToPrimary && $currency->id !== $this->primary->id) {
            Log::debug(sprintf('Convert to primary, from %s to %s', $currency->code, $this->primary->code));
            $converter        = new ExchangeRateConverter();
            $pcCurrentBalance = $converter->convert($currency, $this->primary, $date, $currentBalance);
            $pcOpeningBalance = $converter->convert($currency, $this->primary, $date, $openingBalance);
            $pcVirtualBalance = $converter->convert($currency, $this->primary, $date, $virtualBalance);
            $pcDebtAmount     = null === $debtAmount ? null : $converter->convert($currency, $this->primary, $date, $debtAmount);
        }

        // set opening balance(s) to NULL if the date is null
        if (null === $openingBalanceDate) {
            $openingBalance   = null;
            $pcOpeningBalance = null;
        }

        return [
            'id'                              => (string)$account->id,
            'created_at'                      => $account->created_at->toAtomString(),
            'updated_at'                      => $account->updated_at->toAtomString(),
            'active'                          => $account->active,
            'order'                           => $order,
            'name'                            => $account->name,
            'type'                            => strtolower($accountType),
            'account_role'                    => $accountRole,

            // currency information, structured for 6.3.0.
            'object_has_currency_setting'     => $hasCurrencySettings,

            // currency is object specific or primary, already determined above.
            'currency_id'                     => (string)$currency['id'],
            'currency_code'                   => $currency['code'],
            'currency_symbol'                 => $currency['symbol'],
            'currency_decimal_places'         => $currency['decimal_places'],
            'primary_currency_id'             => (string)$this->primary->id,
            'primary_currency_code'           => $this->primary->code,
            'primary_currency_symbol'         => $this->primary->symbol,
            'primary_currency_decimal_places' => $this->primary->decimal_places,

            // balances, structured for 6.3.0.
            'current_balance'                 => $currentBalance,
            'pc_current_balance'              => $pcCurrentBalance,

            'opening_balance'    => $openingBalance,
            'pc_opening_balance' => $pcOpeningBalance,

            'virtual_balance'    => $virtualBalance,
            'pc_virtual_balance' => $pcVirtualBalance,

            'debt_amount'    => $debtAmount,
            'pc_debt_amount' => $pcDebtAmount,

            'current_balance_date' => $date->toAtomString(),
            'notes'                => $account->meta['notes'] ?? null,
            'monthly_payment_date' => $monthlyPaymentDate,
            'credit_card_type'     => $creditCardType,
            'account_number'       => $account->meta['account_number'] ?? null,
            'iban'                 => '' === $account->iban ? null : $account->iban,
            'bic'                  => $account->meta['BIC'] ?? null,
            'opening_balance_date' => $openingBalanceDate,
            'liability_type'       => $liabilityType,
            'liability_direction'  => $liabilityDirection,
            'interest'             => $interest,
            'interest_period'      => $interestPeriod,
            'include_net_worth'    => $includeNetWorth,
            'longitude'            => $longitude,
            'latitude'             => $latitude,
            'zoom_level'           => $zoomLevel,
            'last_activity'        => array_key_exists('last_activity', $account->meta) ? $account->meta['last_activity']->toAtomString() : null,
            'links'                => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/accounts/%d', $account->id),
                ],
            ],
        ];
    }

    private function getAccountRole(Account $account, string $accountType): ?string
    {
        $accountRole = $account->meta['account_role'] ?? null;
        if ('asset' !== $accountType || '' === (string)$accountRole) {
            return null;
        }

        return $accountRole;
    }

    /**
     * TODO duplicated in the V2 transformer.
     */
    private function getDate(): Carbon
    {
        if (null !== $this->parameters->get('date')) {
            return $this->parameters->get('date');
        }

        return today(config('app.timezone'));
    }

    private function getCCInfo(Account $account, ?string $accountRole, string $accountType): array
    {
        $monthlyPaymentDate = null;
        $creditCardType     = null;
        if ('ccAsset' === $accountRole && 'asset' === $accountType) {
            $creditCardType     = $account->meta['cc_type'] ?? null;
            $monthlyPaymentDate = $account->meta['cc_monthly_payment_date'] ?? null;
        }
        if (null !== $monthlyPaymentDate) {
            // try classic date:
            if (10 === strlen($monthlyPaymentDate)) {
                $object = Carbon::createFromFormat('!Y-m-d', $monthlyPaymentDate, config('app.timezone'));
                if (!$object instanceof Carbon) {
                    $object = today(config('app.timezone'));
                }
                $monthlyPaymentDate = $object->toAtomString();
            }
            if (10 !== strlen((string)$monthlyPaymentDate)) {
                $monthlyPaymentDate = Carbon::parse($monthlyPaymentDate, config('app.timezone'))->toAtomString();
            }
        }

        return [$creditCardType, $monthlyPaymentDate];
    }

    private function getOpeningBalance(Account $account, string $accountType): array
    {
        $openingBalance     = null;
        $openingBalanceDate = null;
//        $pcOpeningBalance   = null;
        if (in_array($accountType, ['asset', 'liabilities'], true)) {
            // grab from meta.
            $openingBalance     = $account->meta['opening_balance_amount'] ?? null;
            $pcOpeningBalance   = null;
            $openingBalanceDate = $account->meta['opening_balance_date'] ?? null;
        }
        if (null !== $openingBalanceDate) {
            $object = Carbon::createFromFormat('Y-m-d H:i:s', $openingBalanceDate, config('app.timezone'));
            if (!$object instanceof Carbon) {
                $object = today(config('app.timezone'));
            }
            $openingBalanceDate = $object->toAtomString();

            // NOW do conversion.
//            if ($this->convertToPrimary && null !== $account->meta['currency']) {
//                $converter        = new ExchangeRateConverter();
//                $pcOpeningBalance = $converter->convert($account->meta['currency'], $this->primary, $object, $openingBalance);
//            }

        }

        return [$openingBalance, $openingBalanceDate];
    }

    private function getInterest(Account $account, string $accountType): array
    {
        $interest       = null;
        $interestPeriod = null;
        if ('liabilities' === $accountType) {
            $interest       = $account->meta['interest'] ?? null;
            $interestPeriod = $account->meta['interest_period'] ?? null;
        }

        return [$interest, $interestPeriod];
    }
}
