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
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Balance;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountEnrichment
 *
 * This class "enriches" accounts and adds data from other tables and models to each account model.
 */
class AccountEnrichment implements EnrichmentInterface
{
//    private array                       $balances;
//    private array                       $currencies;
//    private CurrencyRepositoryInterface $currencyRepository;
//    private TransactionCurrency         $default;
//    private ?Carbon                     $end;
//    private array                       $grouped;
//    private array                       $objectGroups;
//    private AccountRepositoryInterface  $repository;
//    private ?Carbon                     $start;

    private Collection $collection;

    private bool                $convertToNative;
    private User                $user;
    private UserGroup           $userGroup;
    private TransactionCurrency $native;
    private array               $accountIds;
    private array               $accountTypeIds;
    private array               $accountTypes;
    private array               $currencies;
    private array               $meta;

    public function __construct()
    {
        $this->convertToNative = false;
        $this->accountIds      = [];
        $this->currencies      = [];
        $this->accountTypeIds  = [];
        $this->accountTypes    = [];
        $this->meta            = [];
//        $this->repository         = app(AccountRepositoryInterface::class);
//        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
//        $this->start              = null;
//        $this->end                = null;
    }

    #[\Override]
    public function enrichSingle(Model | array $model): Account | array
    {
        Log::debug(__METHOD__);
        $collection = new Collection([$model]);
        $collection = $this->enrich($collection);

        return $collection->first();
    }

    #[\Override]
    /**
     * Do the actual enrichment.
     */
    public function enrich(Collection $collection): Collection
    {
        Log::debug(sprintf('Now doing account enrichment for %d account(s)', $collection->count()));

        // prep local fields
        $this->collection = $collection;
        $this->collectAccountIds();
        $this->getAccountTypes();
        $this->collectMetaData();
//        $this->default      = app('amount')->getNativeCurrency();
//        $this->currencies   = [];
//        $this->balances     = [];
//        $this->objectGroups = [];
//        $this->grouped      = [];
//
//        // do everything here:
//        $this->getLastActivity();
//        $this->collectAccountTypes();
//        $this->collectMetaData();
//        $this->getMetaBalances();
//        $this->getObjectGroups();

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

        $this->appendCollectedData();

        return $this->collection;
    }

    private function getAccountTypes(): void
    {
        $types = AccountType::whereIn('id', $this->accountTypeIds)->get();
        /** @var AccountType $type */
        foreach ($types as $type) {
            $this->accountTypes[(int) $type->id] = $type->type;
        }
    }

    private function collectAccountIds(): void
    {
        /** @var Account $account */
        foreach ($this->collection as $account) {
            $this->accountIds[]     = (int) $account->id;
            $this->accountTypeIds[] = (int) $account->account_type_id;
        }
        $this->accountIds     = array_unique($this->accountIds);
        $this->accountTypeIds = array_unique($this->accountTypeIds);
    }

    private function appendCollectedData(): void
    {

        $accountTypes     = $this->accountTypes;
        $meta             = $this->meta;
        $this->collection = $this->collection->map(function (Account $item) use ($accountTypes, $meta) {
            $item->full_account_type = $accountTypes[(int) $item->account_type_id] ?? null;
            $meta = [];
            if (array_key_exists((int) $item->id, $meta)) {
                foreach ($meta[(int) $item->id] as $name => $value) {
                    $meta[$name] = $value;
                }
            }
            $item->meta              = $meta;

            return $item;
        });
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
        $set = AccountMeta
            ::whereIn('name', ['is_multi_currency', 'currency_id', 'account_role', 'account_number', 'liability_direction', 'interest', 'interest_period', 'current_debt'])
            ->whereIn('account_id', $this->accountIds)
            ->get(['account_meta.id', 'account_meta.account_id', 'account_meta.name', 'account_meta.data'])->toArray();
        /** @var array $entry */
        foreach ($set as $entry) {
            $this->meta[(int) $entry['account_id']][$entry['name']] = (string) $entry['data'];
            if ('currency_id' === $entry['name']) {
                $this->currencies[(int) $entry['data']] = true;
            }
        }
        $currencies = TransactionCurrency::whereIn('id', array_keys($this->currencies))->get();
        foreach ($currencies as $currency) {
            $this->currencies[(int) $currency->id] = $currency;
        }
        foreach ($this->currencies as $id => $currency) {
            if (true === $currency) {
                throw new FireflyException(sprintf('Currency #%d not found.', $id));
            }
        }
        return;


        $metaFields  = $this->repository->getMetaValues($this->collection);
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
        $set = \DB::table('object_groupables')
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

        /** @var \stdClass $entry */
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

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    public function setUser(User $user): void
    {
        $this->user      = $user;
        $this->userGroup = $user->userGroup;
    }

    public function setConvertToNative(bool $convertToNative): void
    {
        $this->convertToNative = $convertToNative;
    }

    public function setNative(TransactionCurrency $native): void
    {
        $this->native = $native;
    }


}
