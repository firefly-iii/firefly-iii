<?php

/*
 * AccountTransformer.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Transformers\V2;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountTransformer
 */
class AccountTransformer extends AbstractTransformer
{
    private array               $accountMeta;
    private array               $accountTypes;
    private array               $balanceDifferences;
    private array               $convertedBalances;
    private array               $currencies;
    private TransactionCurrency $default;
    private array               $fullTypes;
    private array               $lastActivity;
    private array               $objectGroups;
    private array $balances;

    /**
     * This method collects meta-data for one or all accounts in the transformer's collection.
     */
    public function collectMetaData(Collection $objects): Collection
    {
        $this->currencies         = [];
        $this->balances           = [];
        $this->accountMeta        = [];
        $this->accountTypes       = [];
        $this->fullTypes          = [];
        $this->lastActivity       = [];
        $this->objectGroups       = [];
        $this->convertedBalances  = [];
        $this->balanceDifferences = [];

        Log::debug(sprintf('collectMetaData on %d object(s)', $objects->count()));

        // first collect all the "heavy" stuff that relies on ALL data to be present.
        // get last activity:
        $this->getLastActivity($objects);

        // get balances of all accounts
        $this->getMetaBalances($objects);

        // get default currency:
        $this->getDefaultCurrency();

        // collect currency and other meta-data:
        $this->collectAccountMetaData($objects);

        // get account types:
        $this->collectAccountTypes($objects);

        // add balance difference
        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $this->getBalanceDifference($objects, $this->parameters->get('start'), $this->parameters->get('end'));
        }

        // get object groups
        $this->getObjectGroups($objects);

        // sort:
        $objects                  = $this->sortAccounts($objects);

        // if pagination is disabled, do it now:
        if (true === $this->parameters->get('disablePagination')) {
            $page    = (int) $this->parameters->get('page');
            $size    = (int) $this->parameters->get('pageSize');
            $objects = $objects->slice(($page - 1) * $size, $size);
        }

        return $objects;
    }

    private function getLastActivity(Collection $accounts): void
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $lastActivity      = $accountRepository->getLastActivity($accounts);
        foreach ($lastActivity as $row) {
            $this->lastActivity[(int) $row['account_id']] = Carbon::parse($row['date_max'], config('app.timezone'));
        }
    }

    private function getMetaBalances(Collection $accounts): void
    {
        try {
            $this->convertedBalances = app('steam')->finalAccountsBalance($accounts, $this->getDate());
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
        }
    }

    private function getDate(): Carbon
    {
        $date = today(config('app.timezone'));
        if (null !== $this->parameters->get('date')) {
            $date = $this->parameters->get('date');
        }

        return $date;
    }

    private function getDefaultCurrency(): void
    {
        $this->default = app('amount')->getNativeCurrency();
    }

    private function collectAccountMetaData(Collection $accounts): void
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository        = app(CurrencyRepositoryInterface::class);

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $metaFields        = $accountRepository->getMetaValues($accounts, ['currency_id', 'account_role', 'account_number', 'liability_direction', 'interest', 'interest_period', 'current_debt']);
        $currencyIds       = $metaFields->where('name', 'currency_id')->pluck('data')->toArray();

        $currencies        = $repository->getByIds($currencyIds);
        foreach ($currencies as $currency) {
            $id                    = $currency->id;
            $this->currencies[$id] = $currency;
        }
        foreach ($metaFields as $entry) {
            $id                                   = $entry->account_id;
            $this->accountMeta[$id][$entry->name] = $entry->data;
        }
    }

    private function collectAccountTypes(Collection $accounts): void
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accountTypes      = $accountRepository->getAccountTypes($accounts);

        /** @var AccountType $row */
        foreach ($accountTypes as $row) {
            $this->accountTypes[$row->id] = (string) config(sprintf('firefly.shortNamesByFullName.%s', $row->type));
            $this->fullTypes[$row->id]    = $row->type;
        }
    }

    private function getBalanceDifference(Collection $accounts, Carbon $start, Carbon $end): void
    {
        if ('en_US' === config('app.fallback_locale')) {
            throw new FireflyException('Used deprecated method, rethink this.');
        }
        // collect balances, start and end for both native and converted.
        // yes the b is usually used for boolean by idiots but here it's for balance.
        $bStart = [];
        $bEnd   = [];

        try {
            $bStart = app('steam')->finalAccountsBalance($accounts, $start);
            $bEnd   = app('steam')->finalAccountsBalance($accounts, $end);
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
        }

        /** @var Account $account */
        foreach ($accounts as $account) {
            $id = $account->id;
            if (array_key_exists($id, $bStart) && array_key_exists($id, $bEnd)) {
                $this->balanceDifferences[$id] = [
                    'balance'        => bcsub($bEnd[$id]['balance'], $bStart[$id]['balance']),
                    'native_balance' => bcsub($bEnd[$id]['native_balance'], $bStart[$id]['native_balance']),
                ];
            }
        }
    }

    private function getObjectGroups(Collection $accounts): void
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository  = app(AccountRepositoryInterface::class);
        $this->objectGroups = $accountRepository->getObjectGroups($accounts);
    }

    private function sortAccounts(Collection $accounts): Collection
    {
        /** @var null|array $sort */
        $sort = $this->parameters->get('sort');

        if (null === $sort || 0 === count($sort)) {
            return $accounts;
        }

        /**
         * @var string $column
         * @var string $direction
         */
        foreach ($sort as $column => $direction) {
            // account_number + iban
            if ('iban' === $column) {
                $accounts = $this->sortByIban($accounts, $direction);
            }
            if ('balance' === $column) {
                $accounts = $this->sortByBalance($accounts, $direction);
            }
            if ('last_activity' === $column) {
                $accounts = $this->sortByLastActivity($accounts, $direction);
            }
            if ('balance_difference' === $column) {
                $accounts = $this->sortByBalanceDifference($accounts, $direction);
            }
            if ('current_debt' === $column) {
                $accounts = $this->sortByCurrentDebt($accounts, $direction);
            }
        }

        return $accounts;
    }

    private function sortByIban(Collection $accounts, string $direction): Collection
    {
        $meta = $this->accountMeta;

        return $accounts->sort(function (Account $left, Account $right) use ($meta, $direction) {
            $leftIban  = trim(sprintf('%s%s', $left->iban, $meta[$left->id]['account_number'] ?? ''));
            $rightIban = trim(sprintf('%s%s', $right->iban, $meta[$right->id]['account_number'] ?? ''));
            if ('asc' === $direction) {
                return strcasecmp($leftIban, $rightIban);
            }

            return strcasecmp($rightIban, $leftIban);
        });
    }

    private function sortByBalance(Collection $accounts, string $direction): Collection
    {
        $balances = $this->convertedBalances;

        return $accounts->sort(function (Account $left, Account $right) use ($balances, $direction) {
            $leftBalance  = (float) ($balances[$left->id]['native_balance'] ?? 0);
            $rightBalance = (float) ($balances[$right->id]['native_balance'] ?? 0);
            if ('asc' === $direction) {
                return $leftBalance <=> $rightBalance;
            }

            return $rightBalance <=> $leftBalance;
        });
    }

    private function sortByLastActivity(Collection $accounts, string $direction): Collection
    {
        $dates = $this->lastActivity;

        return $accounts->sort(function (Account $left, Account $right) use ($dates, $direction) {
            $leftDate  = $dates[$left->id] ?? Carbon::create(1900, 1, 1, 0, 0, 0);
            $rightDate = $dates[$right->id] ?? Carbon::create(1900, 1, 1, 0, 0, 0);
            if ('asc' === $direction) {
                return $leftDate->gt($rightDate) ? 1 : -1;
            }

            return $rightDate->gt($leftDate) ? 1 : -1;
        });
    }

    private function sortByBalanceDifference(Collection $accounts, string $direction): Collection
    {
        $balances = $this->balanceDifferences;

        return $accounts->sort(function (Account $left, Account $right) use ($balances, $direction) {
            $leftBalance  = (float) ($balances[$left->id]['native_balance'] ?? 0);
            $rightBalance = (float) ($balances[$right->id]['native_balance'] ?? 0);
            if ('asc' === $direction) {
                return $leftBalance <=> $rightBalance;
            }

            return $rightBalance <=> $leftBalance;
        });
    }

    private function sortByCurrentDebt(Collection $accounts, string $direction): Collection
    {
        $amounts = $this->accountMeta;

        return $accounts->sort(function (Account $left, Account $right) use ($amounts, $direction) {
            $leftCurrent  = (float) ($amounts[$left->id]['current_debt'] ?? 0);
            $rightCurrent = (float) ($amounts[$right->id]['current_debt'] ?? 0);
            if ('asc' === $direction) {
                return $leftCurrent <=> $rightCurrent;
            }

            return $rightCurrent <=> $leftCurrent;
        });
    }

    /**
     * Transform the account.
     */
    public function transform(Account $account): array
    {
        $id                 = $account->id;

        // various meta
        $accountRole        = $this->accountMeta[$id]['account_role'] ?? null;
        $accountType        = $this->accountTypes[$id];
        $order              = $account->order;

        // liability type
        $liabilityType      = 'liabilities' === $accountType ? $this->fullTypes[$id] : null;
        $liabilityDirection = $this->accountMeta[$id]['liability_direction'] ?? null;
        $interest           = $this->accountMeta[$id]['interest'] ?? null;
        $interestPeriod     = $this->accountMeta[$id]['interest_period'] ?? null;
        $currentDebt        = $this->accountMeta[$id]['current_debt'] ?? null;

        // no currency? use default
        $currency           = $this->default;
        if (array_key_exists($id, $this->accountMeta) && 0 !== (int) ($this->accountMeta[$id]['currency_id'] ?? 0)) {
            $currency = $this->currencies[(int) $this->accountMeta[$id]['currency_id']];
        }
        // amounts and calculation.
        $balance            = $this->balances[$id]['balance'] ?? null;
        $nativeBalance      = $this->convertedBalances[$id]['native_balance'] ?? null;

        // no order for some accounts:
        if (!in_array(strtolower($accountType), ['liability', 'liabilities', 'asset'], true)) {
            $order = null;
        }

        // object group
        $objectGroupId      = $this->objectGroups[$id]['id'] ?? null;
        $objectGroupOrder   = $this->objectGroups[$id]['order'] ?? null;
        $objectGroupTitle   = $this->objectGroups[$id]['title'] ?? null;

        // balance difference
        $diffStart          = null;
        $diffEnd            = null;
        $balanceDiff        = null;
        $nativeBalanceDiff  = null;
        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $diffStart         = $this->parameters->get('start')->toAtomString();
            $diffEnd           = $this->parameters->get('end')->toAtomString();
            $balanceDiff       = $this->balanceDifferences[$id]['balance'] ?? null;
            $nativeBalanceDiff = $this->balanceDifferences[$id]['native_balance'] ?? null;
        }

        return [
            'id'                             => (string) $account->id,
            'created_at'                     => $account->created_at->toAtomString(),
            'updated_at'                     => $account->updated_at->toAtomString(),
            'active'                         => $account->active,
            'order'                          => $order,
            'name'                           => $account->name,
            'iban'                           => '' === (string) $account->iban ? null : $account->iban,
            'account_number'                 => $this->accountMeta[$id]['account_number'] ?? null,
            'type'                           => strtolower($accountType),
            'account_role'                   => $accountRole,
            'currency_id'                    => (string) $currency->id,
            'currency_code'                  => $currency->code,
            'currency_symbol'                => $currency->symbol,
            'currency_decimal_places'        => $currency->decimal_places,

            'native_currency_id'             => (string) $this->default->id,
            'native_currency_code'           => $this->default->code,
            'native_currency_symbol'         => $this->default->symbol,
            'native_currency_decimal_places' => $this->default->decimal_places,

            // balance:
            'current_balance'                => $balance,
            'native_current_balance'         => $nativeBalance,
            'current_balance_date'           => $this->getDate()->endOfDay()->toAtomString(),

            // balance difference
            'balance_difference'             => $balanceDiff,
            'native_balance_difference'      => $nativeBalanceDiff,
            'balance_difference_start'       => $diffStart,
            'balance_difference_end'         => $diffEnd,

            // more meta
            'last_activity'                  => array_key_exists($id, $this->lastActivity) ? $this->lastActivity[$id]->toAtomString() : null,

            // liability stuff
            'liability_type'                 => $liabilityType,
            'liability_direction'            => $liabilityDirection,
            'interest'                       => $interest,
            'interest_period'                => $interestPeriod,
            'current_debt'                   => $currentDebt,

            // object group
            'object_group_id'                => null !== $objectGroupId ? (string) $objectGroupId : null,
            'object_group_order'             => $objectGroupOrder,
            'object_group_title'             => $objectGroupTitle,

            //            'notes'                   => $this->repository->getNoteText($account),
            //            'monthly_payment_date'    => $monthlyPaymentDate,
            //            'credit_card_type'        => $creditCardType,
            //            'bic'                     => $this->repository->getMetaValue($account, 'BIC'),
            //            'virtual_balance'         => number_format((float) $account->virtual_balance, $decimalPlaces, '.', ''),
            //            'opening_balance'         => $openingBalance,
            //            'opening_balance_date'    => $openingBalanceDate,
            //            'include_net_worth'       => $includeNetWorth,
            //            'longitude'               => $longitude,
            //            'latitude'                => $latitude,
            //            'zoom_level'              => $zoomLevel,
            'links'                          => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/'.$account->id,
                ],
            ],
        ];
    }
}
