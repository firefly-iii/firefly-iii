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
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class AccountTransformer
 */
class AccountTransformer extends AbstractTransformer
{
    protected AccountRepositoryInterface $repository;

    /**
     * AccountTransformer constructor.
     */
    public function __construct()
    {
        $this->parameters = new ParameterBag();
        $this->repository = app(AccountRepositoryInterface::class);
    }

    /**
     * Transform the account.
     *
     * @throws FireflyException
     */
    public function transform(Account $account): array
    {
        $this->repository->setUser($account->user);

        // get account type:
        $fullType           = $account->accountType->type;
        $accountType        = (string) config(sprintf('firefly.shortNamesByFullName.%s', $fullType));
        $liabilityType      = (string) config(sprintf('firefly.shortLiabilityNameByFullName.%s', $fullType));
        $liabilityType      = '' === $liabilityType ? null : strtolower($liabilityType);
        $liabilityDirection = $this->repository->getMetaValue($account, 'liability_direction');
        $convertToNative    = Amount::convertToNative();

        // get account role (will only work if the type is asset).
        $default     = Amount::getDefaultCurrency();
        $accountRole = $this->getAccountRole($account, $accountType);
        $date        = $this->getDate();
        $date->endOfDay();

        [$currencyId, $currencyCode, $currencySymbol, $decimalPlaces] = $this->getCurrency($account, $default);
        [$creditCardType, $monthlyPaymentDate] = $this->getCCInfo($account, $accountRole, $accountType);
        [$openingBalance, $nativeOpeningBalance, $openingBalanceDate] = $this->getOpeningBalance($account, $accountType, $convertToNative);
        [$interest, $interestPeriod] = $this->getInterest($account, $accountType);

        if (!$convertToNative) {
            // reset default currency to NULL, not interesting.
            $default = null;
        }

        $openingBalance  = app('steam')->bcround($openingBalance, $decimalPlaces);
        $includeNetWorth = '0' !== $this->repository->getMetaValue($account, 'include_net_worth');
        $longitude       = null;
        $latitude        = null;
        $zoomLevel       = null;
        $location        = $this->repository->getLocation($account);
        if (null !== $location) {
            $longitude = $location->longitude;
            $latitude  = $location->latitude;
            $zoomLevel = (int) $location->zoom_level;
        }

        // no order for some accounts:
        $order = $account->order;
        if (!in_array(strtolower($accountType), ['liability', 'liabilities', 'asset'], true)) {
            $order = null;
        }
        // balance, native balance, virtual balance, native virtual balance?
        $finalBalance         = Steam::finalAccountBalance($account, $date);
        if($convertToNative) {
            $finalBalance['balance'] = $finalBalance[$currencyCode] ?? '0';
        }

        $currentBalance       = app('steam')->bcround($finalBalance['balance'] ?? '0', $decimalPlaces);
        $nativeCurrentBalance = $convertToNative ? app('steam')->bcround($finalBalance['native_balance'] ?? '0', $default->decimal_places) : null;

        return [
            'id'                             => (string) $account->id,
            'created_at'                     => $account->created_at->toAtomString(),
            'updated_at'                     => $account->updated_at->toAtomString(),
            'active'                         => $account->active,
            'order'                          => $order,
            'name'                           => $account->name,
            'type'                           => strtolower($accountType),
            'account_role'                   => $accountRole,
            'currency_id'                    => $currencyId,
            'currency_code'                  => $currencyCode,
            'currency_symbol'                => $currencySymbol,
            'currency_decimal_places'        => $decimalPlaces,
            'native_currency_id'             => null === $default ? null : (string) $default->id,
            'native_currency_code'           => $default?->code,
            'native_currency_symbol'         => $default?->symbol,
            'native_currency_decimal_places' => $default?->decimal_places,
            'current_balance'                => $currentBalance,
            'native_current_balance'         => $nativeCurrentBalance,
            'current_balance_date'           => $date->toAtomString(),
            'notes'                          => $this->repository->getNoteText($account),
            'monthly_payment_date'           => $monthlyPaymentDate,
            'credit_card_type'               => $creditCardType,
            'account_number'                 => $this->repository->getMetaValue($account, 'account_number'),
            'iban'                           => '' === $account->iban ? null : $account->iban,
            'bic'                            => $this->repository->getMetaValue($account, 'BIC'),
            'virtual_balance'                => app('steam')->bcround($account->virtual_balance, $decimalPlaces),
            'native_virtual_balance'         => $convertToNative ? app('steam')->bcround($account->native_virtual_balance, $default->decimal_places) : null,
            'opening_balance'                => $openingBalance,
            'native_opening_balance'         => $nativeOpeningBalance,
            'opening_balance_date'           => $openingBalanceDate,
            'liability_type'                 => $liabilityType,
            'liability_direction'            => $liabilityDirection,
            'interest'                       => $interest,
            'interest_period'                => $interestPeriod,
            'current_debt'                   => $this->repository->getMetaValue($account, 'current_debt'),
            'include_net_worth'              => $includeNetWorth,
            'longitude'                      => $longitude,
            'latitude'                       => $latitude,
            'zoom_level'                     => $zoomLevel,
            'links'                          => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/' . $account->id,
                ],
            ],
        ];
    }

    private function getAccountRole(Account $account, string $accountType): ?string
    {
        $accountRole = $this->repository->getMetaValue($account, 'account_role');
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

    private function getCurrency(Account $account, TransactionCurrency $default): array
    {
        $currency = $this->repository->getAccountCurrency($account);

        // only grab default when result is null:
        if (null === $currency) {
            $currency = $default;
        }
        $currencyId     = (string) $currency->id;
        $currencyCode   = $currency->code;
        $decimalPlaces  = $currency->decimal_places;
        $currencySymbol = $currency->symbol;

        return [$currencyId, $currencyCode, $currencySymbol, $decimalPlaces];
    }

    private function getCCInfo(Account $account, ?string $accountRole, string $accountType): array
    {
        $monthlyPaymentDate = null;
        $creditCardType     = null;
        if ('ccAsset' === $accountRole && 'asset' === $accountType) {
            $creditCardType     = $this->repository->getMetaValue($account, 'cc_type');
            $monthlyPaymentDate = $this->repository->getMetaValue($account, 'cc_monthly_payment_date');
        }
        if (null !== $monthlyPaymentDate) {
            // try classic date:
            if (10 === strlen($monthlyPaymentDate)) {
                $object = Carbon::createFromFormat('!Y-m-d', $monthlyPaymentDate, config('app.timezone'));
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

    /**
     * TODO refactor call to get~OpeningBalanceAmount / Date because it is a lot of queries
     */
    private function getOpeningBalance(Account $account, string $accountType, bool $convertToNative): array
    {
        $openingBalance       = null;
        $openingBalanceDate   = null;
        $nativeOpeningBalance = null;
        if (in_array($accountType, ['asset', 'liabilities'], true)) {
            $openingBalance       = $this->repository->getOpeningBalanceAmount($account, false);
            $nativeOpeningBalance = $this->repository->getOpeningBalanceAmount($account, true);
            $openingBalanceDate   = $this->repository->getOpeningBalanceDate($account);
        }
        if (null !== $openingBalanceDate) {
            $object = Carbon::createFromFormat('Y-m-d H:i:s', $openingBalanceDate, config('app.timezone'));
            if (null === $object) {
                $object = today(config('app.timezone'));
            }
            $openingBalanceDate = $object->toAtomString();
        }

        return [$openingBalance, $nativeOpeningBalance, $openingBalanceDate];
    }

    private function getInterest(Account $account, string $accountType): array
    {
        $interest       = null;
        $interestPeriod = null;
        if ('liabilities' === $accountType) {
            $interest       = $this->repository->getMetaValue($account, 'interest');
            $interestPeriod = $this->repository->getMetaValue($account, 'interest_period');
        }

        return [$interest, $interestPeriod];
    }
}
