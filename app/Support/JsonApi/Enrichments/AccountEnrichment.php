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
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Facades\Balance;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Override;
use stdClass;

/**
 * Class AccountEnrichment
 *
 * This class "enriches" accounts and adds data from other tables and models to each account model.
 */
class AccountEnrichment implements EnrichmentInterface
{
    private array                       $balances;
    private Collection                  $collection;
    private array                       $currencies;
    private CurrencyRepositoryInterface $currencyRepository;
    private TransactionCurrency         $default;
    private ?Carbon                     $end;
    private array                       $grouped;
    private array                       $objectGroups;
    private AccountRepositoryInterface  $repository;
    private ?Carbon                     $start;

    public function __construct()
    {
        $this->repository         = app(AccountRepositoryInterface::class);
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        $this->start              = null;
        $this->end                = null;
    }

    #[Override]
    public function enrichSingle(Model $model): Account
    {
        Log::debug(__METHOD__);
        $collection = new Collection([$model]);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    #[Override]
    /**
     * Do the actual enrichment.
     */
    public function enrich(Collection $collection): Collection
    {
        Log::debug(sprintf('Now doing account enrichment for %d account(s)', $collection->count()));
        // prep local fields
        $this->collection   = $collection;
        $this->default      = app('amount')->getDefaultCurrency();
        $this->currencies   = [];
        $this->balances     = [];
        $this->objectGroups = [];
        $this->grouped      = [];

        // do everything here:
        $this->getLastActivity();
        $this->collectAccountTypes();
        $this->collectMetaData();
        $this->getMetaBalances();
        $this->getObjectGroups();

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
        $lastActivity = $this->repository->getLastActivity($this->collection);
        foreach ($lastActivity as $row) {
            $this->collection->where('id', $row['account_id'])->first()->last_activity = Carbon::parse($row['date_max'], config('app.timezone'));
        }
    }

    /**
     * TODO this method refers to a single-use method inside Steam that could be moved here.
     */
    private function collectAccountTypes(): void
    {
        $accountTypes = $this->repository->getAccountTypes($this->collection);
        $types        = [];

        /** @var AccountType $row */
        foreach ($accountTypes as $row) {
            $types[$row->id] = $row->type;
        }
        $this->collection->transform(function (Account $account) use ($types) {
            $account->account_type_string = $types[$account->id];

            return $account;
        });
    }

    private function collectMetaData(): void
    {
        $metaFields  = $this->repository->getMetaValues($this->collection, ['is_multi_currency', 'currency_id', 'account_role', 'account_number', 'liability_direction', 'interest', 'interest_period', 'current_debt']);
        $currencyIds = $metaFields->where('name', 'currency_id')->pluck('data')->toArray();

        $currencies = [];
        foreach ($this->currencyRepository->getByIds($currencyIds) as $currency) {
            $id              = $currency->id;
            $currencies[$id] = $currency;
        }

        $this->collection->transform(function (Account $account) use ($metaFields, $currencies) {
            $set = $metaFields->where('account_id', $account->id);
            foreach ($set as $entry) {
                $account->{$entry->name} = $entry->data;
                if ('currency_id' === $entry->name) {
                    $id                               = (int) $entry->data;
                    $account->currency_name           = $currencies[$id]?->name;
                    $account->currency_code           = $currencies[$id]?->code;
                    $account->currency_symbol         = $currencies[$id]?->symbol;
                    $account->currency_decimal_places = $currencies[$id]?->decimal_places;
                }
            }

            return $account;
        });
    }

    private function getMetaBalances(): void
    {
        $this->balances = Balance::getAccountBalances($this->collection, today());
        $balances       = $this->balances;
        $default        = $this->default;

        // get start and end, so the balance difference can be generated.
        $start = null;
        $end   = null;
        if (null !== $this->start) {
            $start = Balance::getAccountBalances($this->collection, $this->start);
        }
        if (null !== $this->end) {
            $end = Balance::getAccountBalances($this->collection, $this->end);
        }

        $this->collection->transform(function (Account $account) use ($balances, $default, $start, $end) {
            $converter = new ExchangeRateConverter();
            $native    = [
                'currency_id'             => $this->default->id,
                'currency_name'           => $this->default->name,
                'currency_code'           => $this->default->code,
                'currency_symbol'         => $this->default->symbol,
                'currency_decimal_places' => $this->default->decimal_places,
                'balance'                 => '0',
                'period_start_balance'    => null,
                'period_end_balance'      => null,
                'balance_difference'      => null,
            ];
            if (array_key_exists($account->id, $balances)) {
                $set = [];
                foreach ($balances[$account->id] as $currencyId => $entry) {
                    $left  = $start[$account->id][$currencyId]['balance'] ?? null;
                    $right = $end[$account->id][$currencyId]['balance'] ?? null;
                    $diff  = null;
                    if (null !== $left && null !== $right) {
                        $diff = bcsub($right, $left);
                    }

                    $item  = [
                        'currency_id'             => $entry['currency']->id,
                        'currency_name'           => $entry['currency']->name,
                        'currency_code'           => $entry['currency']->code,
                        'currency_symbol'         => $entry['currency']->symbol,
                        'currency_decimal_places' => $entry['currency']->decimal_places,
                        'balance'                 => $entry['balance'],
                        'period_start_balance'    => $left,
                        'period_end_balance'      => $right,
                        'balance_difference'      => $diff,
                    ];
                    $set[] = $item;
                    if ($converter->enabled()) {
                        $native['balance'] = bcadd($native['balance'], $converter->convert($entry['currency'], $default, today(), $entry['balance']));
                        if (null !== $diff) {
                            $native['period_start_balance'] = $converter->convert($entry['currency'], $default, today(), $item['period_start_balance']);
                            $native['period_end_balance']   = $converter->convert($entry['currency'], $default, today(), $item['period_end_balance']);
                            $native['balance_difference']   = bcsub($native['period_end_balance'], $native['period_start_balance']);
                        }
                    }
                }
                $account->balance = $set;
                if ($converter->enabled()) {
                    $account->native_balance = $native;
                }
            }

            return $account;
        });
    }

    private function getObjectGroups(): void
    {
        $set = DB::table('object_groupables')
                  ->where('object_groupable_type', Account::class)
                  ->whereIn('object_groupable_id', $this->collection->pluck('id')->toArray())
                  ->distinct()
                  ->get(['object_groupables.object_groupable_id', 'object_groupables.object_group_id']);
        // get the groups:
        $groupIds = $set->pluck('object_group_id')->toArray();
        $groups   = ObjectGroup::whereIn('id', $groupIds)->get();

        /** @var ObjectGroup $group */
        foreach ($groups as $group) {
            $this->objectGroups[$group->id] = $group;
        }

        /** @var stdClass $entry */
        foreach ($set as $entry) {
            $this->grouped[(int) $entry->object_groupable_id] = (int) $entry->object_group_id;
        }
        $this->collection->transform(function (Account $account) {
            $account->object_group_id = $this->grouped[$account->id] ?? null;
            if (null !== $account->object_group_id) {
                $account->object_group_title = $this->objectGroups[$account->object_group_id]->title;
                $account->object_group_order = $this->objectGroups[$account->object_group_id]->order;
            }

            return $account;
        });
    }

    public function setEnd(?Carbon $end): void
    {
        $this->end = $end;
    }

    public function setStart(?Carbon $start): void
    {
        $this->start = $start;
    }
}
