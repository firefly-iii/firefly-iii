<?php
/*
 * AccountEnricher.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support\JsonApi\Enrichments;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountEnrichment
 *
 * This class "enriches" accounts and adds data from other tables and models to each account model.
 */
class AccountEnrichment implements EnrichmentInterface
{
    private Collection $collection;
    private array      $currencies;

    #[\Override]
    /**
     * Do the actual enrichment.
     */
    public function enrich(Collection $collection): Collection
    {
        Log::debug(sprintf('Now doing account enrichment for %d account(s)', $collection->count()));
        // prep local fields
        $this->collection = $collection;
        $this->currencies = [];

        // do everything here:
        $this->getLastActivity();
        // $this->getMetaBalances();
        $this->collectAccountTypes();
        $this->collectMetaData();

//        $this->collection->transform(function (Account $account) {
//            $account->user_array = ['id' => 1, 'bla bla' => 'bla'];
//            $account->balances   = collect([
//                ['balance_id' => 1, 'balance' => 5],
//                ['balance_id' => 2, 'balance' => 5],
//                ['balance_id' => 3, 'balance' => 5],
//            ]);
//
//            return $account;
//        });

        return $this->collection;
    }

    /**
     * TODO this method refers to a single-use method inside Steam that could be moved here.
     */
    private function getLastActivity(): void
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $lastActivity      = $accountRepository->getLastActivity($this->collection);
        foreach ($lastActivity as $row) {
            $this->collection->where('id', $row['account_id'])->first()->last_activity = Carbon::parse($row['date_max'], config('app.timezone'));
        }
    }

    /**
     * TODO this method refers to a single-use method inside Steam that could be moved here.
     */
    private function getMetaBalances(): void
    {
        try {
            $array = app('steam')->balancesByAccountsConverted($this->collection, today());
        } catch (FireflyException $e) {
            Log::error(sprintf('Could not load balances: %s', $e->getMessage()));

            return;
        }
        foreach ($array as $accountId => $row) {
            $this->collection->where('id', $accountId)->first()->balance        = $row['balance'];
            $this->collection->where('id', $accountId)->first()->native_balance = $row['native_balance'];
        }
    }

    /**
     * TODO this method refers to a single-use method inside Steam that could be moved here.
     */
    private function collectAccountTypes(): void
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accountTypes      = $accountRepository->getAccountTypes($this->collection);
        $types             = [];

        /** @var AccountType $row */
        foreach ($accountTypes as $row) {
            $types[$row->id] = $row->type;
        }
        $this->collection->transform(function (Account $account) use ($types) {
            $account->type = $types[$account->id];

            return $account;
        });
    }

    private function collectMetaData(): void
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var CurrencyRepositoryInterface $repository */
        $repository        = app(CurrencyRepositoryInterface::class);

        $metaFields        = $accountRepository->getMetaValues($this->collection, ['currency_id', 'account_role', 'account_number', 'liability_direction', 'interest', 'interest_period', 'current_debt']);
        $currencyIds       = $metaFields->where('name', 'currency_id')->pluck('data')->toArray();

        $currencies        = [];
        foreach ($repository->getByIds($currencyIds) as $currency) {
            $id              = $currency->id;
            $currencies[$id] = $currency;
        }

        $this->collection->transform(function (Account $account) use ($metaFields, $currencies) {
            $set = $metaFields->where('account_id', $account->id);
            foreach ($set as $entry) {
                $account->{$entry->name} = $entry->data;
                if ('currency_id' === $entry->name) {
                    $id                               = (int) $entry->data;
                    $account->currency_code           = $currencies[$id]?->code;
                    $account->currency_symbol         = $currencies[$id]?->symbol;
                    $account->currency_decimal_places = $currencies[$id]?->decimal_places;
                }
            }

            return $account;
        });
    }
}
