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
    protected AccountRepositoryInterface $repository;
    protected bool                       $convertToNative;
    protected TransactionCurrency        $native;

    /**
     * AccountTransformer constructor.
     */
    public function __construct()
    {
        $this->parameters      = new ParameterBag();
        $this->repository      = app(AccountRepositoryInterface::class);
        $this->convertToNative = Amount::convertToNative();
        $this->native          = Amount::getNativeCurrency();
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
        $accountType                                                  = (string) config(sprintf('firefly.shortNamesByFullName.%s', $account->full_account_type));
        $liabilityType                                                = (string) config(sprintf('firefly.shortLiabilityNameByFullName.%s', $account->full_account_type));
        $liabilityType                                                = '' === $liabilityType ? null : strtolower($liabilityType);

        $liabilityDirection                                           = $account->meta['liability_direction'] ?? null;
        // get account role (will only work if the type is asset).
        $accountRole                                                  = $this->getAccountRole($account, $accountType);

        // date (for balance etc.)
        $date                                                         = $this->getDate();
        $date->endOfDay();

        [$creditCardType, $monthlyPaymentDate]                        = $this->getCCInfo($account, $accountRole, $accountType);
        [$openingBalance, $nativeOpeningBalance, $openingBalanceDate] = $this->getOpeningBalance($account, $accountType);
        [$interest, $interestPeriod]                                  = $this->getInterest($account, $accountType);

        $native                                                       = $this->native;
        if (!$this->convertToNative) {
            // reset native currency to NULL, not interesting.
            $native = null;
        }

        $decimalPlaces                                                = (int) $account->meta['currency']?->decimal_places;
        $decimalPlaces                                                = 0 === $decimalPlaces ? 2 : $decimalPlaces;
        $openingBalance                                               = Steam::bcround($openingBalance, $decimalPlaces);
        $includeNetWorth                                              = '0' !== ($account->meta['include_net_worth'] ?? null);
        $longitude                                                    = $account->meta['location']['longitude'] ?? null;
        $latitude                                                     = $account->meta['location']['latitude'] ?? null;
        $zoomLevel                                                    = $account->meta['location']['zoom_level'] ?? null;

        // no order for some accounts:
        $order                                                        = $account->order;
        if (!in_array(strtolower($accountType), ['liability', 'liabilities', 'asset'], true)) {
            $order = null;
        }
        // balance, native balance, virtual balance, native virtual balance?
        Log::debug(sprintf('transform: Call finalAccountBalance with date/time "%s"', $date->toIso8601String()));
        $finalBalance                                                 = Steam::finalAccountBalance($account, $date, $this->native, $this->convertToNative);
        if ($this->convertToNative) {
            $finalBalance['balance'] = $finalBalance[$account->meta['currency']?->code] ?? '0';
        }

        $currentBalance                                               = Steam::bcround($finalBalance['balance'] ?? '0', $decimalPlaces);
        $nativeCurrentBalance                                         = $this->convertToNative ? Steam::bcround($finalBalance['native_balance'] ?? '0', $native->decimal_places) : null;

        return [
            'id'                             => (string) $account->id,
            'created_at'                     => $account->created_at->toAtomString(),
            'updated_at'                     => $account->updated_at->toAtomString(),
            'active'                         => $account->active,
            'order'                          => $order,
            'name'                           => $account->name,
            'type'                           => strtolower($accountType),
            'account_role'                   => $accountRole,
            'currency_id'                    => $account->meta['currency_id'] ?? null,
            'currency_code'                  => $account->meta['currency']?->code,
            'currency_symbol'                => $account->meta['currency']?->symbol,
            'currency_decimal_places'        => $account->meta['currency']?->decimal_places,
            'native_currency_id'             => null === $native ? null : (string) $native->id,
            'native_currency_code'           => $native?->code,
            'native_currency_symbol'         => $native?->symbol,
            'native_currency_decimal_places' => $native?->decimal_places,
            'current_balance'                => $currentBalance,
            'native_current_balance'         => $nativeCurrentBalance,
            'current_balance_date'           => $date->toAtomString(),
            'notes'                          => $account->meta['notes'] ?? null,
            'monthly_payment_date'           => $monthlyPaymentDate,
            'credit_card_type'               => $creditCardType,
            'account_number'                 => $account->meta['account_number'] ?? null,
            'iban'                           => '' === $account->iban ? null : $account->iban,
            'bic'                            => $account->meta['BIC'] ?? null,
            'virtual_balance'                => Steam::bcround($account->virtual_balance, $decimalPlaces),
            'native_virtual_balance'         => $this->convertToNative ? Steam::bcround($account->native_virtual_balance, $native->decimal_places) : null,
            'opening_balance'                => $openingBalance,
            'native_opening_balance'         => $nativeOpeningBalance,
            'opening_balance_date'           => $openingBalanceDate,
            'liability_type'                 => $liabilityType,
            'liability_direction'            => $liabilityDirection,
            'interest'                       => $interest,
            'interest_period'                => $interestPeriod,
            'current_debt'                   => $account->meta['current_debt'] ?? null,
            'include_net_worth'              => $includeNetWorth,
            'longitude'                      => $longitude,
            'latitude'                       => $latitude,
            'zoom_level'                     => $zoomLevel,
            'links'                          => [
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
        if ('asset' !== $accountType || '' === (string) $accountRole) {
            $accountRole = null;
        }

        return $accountRole;
    }

    /**
     * TODO duplicated in the V2 transformer.
     */
    private function getDate(): Carbon
    {
        $date = today(config('app.timezone'));
        if (null !== $this->parameters->get('date')) {
            $date = $this->parameters->get('date');
        }

        return $date;
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
                $object             = Carbon::createFromFormat('!Y-m-d', $monthlyPaymentDate, config('app.timezone'));
                if (null === $object) {
                    $object = today(config('app.timezone'));
                }
                $monthlyPaymentDate = $object->toAtomString();
            }
            if (10 !== strlen($monthlyPaymentDate)) {
                $monthlyPaymentDate = Carbon::parse($monthlyPaymentDate, config('app.timezone'))->toAtomString();
            }
        }

        return [$creditCardType, $monthlyPaymentDate];
    }

    private function getOpeningBalance(Account $account, string $accountType): array
    {
        $openingBalance       = null;
        $openingBalanceDate   = null;
        $nativeOpeningBalance = null;
        if (in_array($accountType, ['asset', 'liabilities'], true)) {
            // grab from meta.
            $openingBalance       = $account->meta['opening_balance_amount'] ?? null;
            $nativeOpeningBalance = null;
            $openingBalanceDate   = $account->meta['opening_balance_date'] ?? null;
        }
        if (null !== $openingBalanceDate) {
            $object             = Carbon::createFromFormat('Y-m-d H:i:s', $openingBalanceDate, config('app.timezone'));
            if (null === $object) {
                $object = today(config('app.timezone'));
            }
            $openingBalanceDate = $object->toAtomString();

            // NOW do conversion.
            if ($this->convertToNative && null !== $account->meta['currency']) {
                $converter            = new ExchangeRateConverter();
                $nativeOpeningBalance = $converter->convert($account->meta['currency'], $this->native, $object, $openingBalance);
            }

        }

        return [$openingBalance, $nativeOpeningBalance, $openingBalanceDate];
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
