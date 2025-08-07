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
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Location;
use FireflyIII\Models\Note;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Override;

/**
 * Class AccountEnrichment
 *
 * This class "enriches" accounts and adds data from other tables and models to each account model.
 */
class AccountEnrichment implements EnrichmentInterface
{
    private array               $ids             = [];
    private array               $accountTypeIds  = [];
    private array               $accountTypes    = [];
    private Collection          $collection;
    private array               $currencies      = [];
    private array               $locations       = [];
    private array               $meta            = [];
    private TransactionCurrency $primaryCurrency;
    private array               $notes           = [];
    private array               $openingBalances = [];
    private User                $user;
    private UserGroup           $userGroup;
    private array               $lastActivities  = [];
    private ?Carbon             $date            = null;
    private bool                $convertToPrimary;
    private array               $balances        = [];
    private array               $objectGroups    = [];
    private array               $mappedObjects   = [];

    /**
     * TODO The account enricher must do conversion from and to the primary currency.
     */
    public function __construct()
    {
        $this->primaryCurrency  = Amount::getPrimaryCurrency();
        $this->convertToPrimary = Amount::convertToPrimary();
    }

    #[Override]
    public function enrichSingle(array|Model $model): Account|array
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
        $this->collection = $collection;
        $this->collectIds();
        $this->getAccountTypes();
        $this->collectMetaData();
        $this->collectNotes();
        $this->collectLastActivities();
        $this->collectLocations();
        $this->collectOpeningBalances();
        $this->collectObjectGroups();
        $this->collectBalances();
        $this->appendCollectedData();

        return $this->collection;
    }

    private function collectIds(): void
    {
        /** @var Account $account */
        foreach ($this->collection as $account) {
            $this->ids[]            = (int)$account->id;
            $this->accountTypeIds[] = (int)$account->account_type_id;
        }
        $this->ids            = array_unique($this->ids);
        $this->accountTypeIds = array_unique($this->accountTypeIds);
    }

    private function getAccountTypes(): void
    {
        $types = AccountType::whereIn('id', $this->accountTypeIds)->get();

        /** @var AccountType $type */
        foreach ($types as $type) {
            $this->accountTypes[(int)$type->id] = $type->type;
        }
    }

    private function collectMetaData(): void
    {
        $set                 = AccountMeta::whereIn('name', ['is_multi_currency', 'include_net_worth', 'currency_id', 'account_role', 'account_number', 'BIC', 'liability_direction', 'interest', 'interest_period', 'current_debt'])
            ->whereIn('account_id', $this->ids)
            ->get(['account_meta.id', 'account_meta.account_id', 'account_meta.name', 'account_meta.data'])->toArray()
        ;

        /** @var array $entry */
        foreach ($set as $entry) {
            $this->meta[(int)$entry['account_id']][$entry['name']] = (string)$entry['data'];
            if ('currency_id' === $entry['name']) {
                $this->currencies[(int)$entry['data']] = true;
            }
        }
        if (count($this->currencies) > 0) {
            $currencies = TransactionCurrency::whereIn('id', array_keys($this->currencies))->get();
            foreach ($currencies as $currency) {
                $this->currencies[(int)$currency->id] = $currency;
            }
        }
        $this->currencies[0] = $this->primaryCurrency;
        foreach ($this->currencies as $id => $currency) {
            if (true === $currency) {
                throw new FireflyException(sprintf('Currency #%d not found.', $id));
            }
        }
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->ids)
            ->whereNotNull('notes.text')
            ->where('notes.text', '!=', '')
            ->where('noteable_type', Account::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int)$note['noteable_id']] = (string)$note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    private function collectLocations(): void
    {
        $locations = Location::query()->whereIn('locatable_id', $this->ids)
            ->where('locatable_type', Account::class)->get(['locations.locatable_id', 'locations.latitude', 'locations.longitude', 'locations.zoom_level'])->toArray()
        ;
        foreach ($locations as $location) {
            $this->locations[(int)$location['locatable_id']]
                = [
                    'latitude'   => (float)$location['latitude'],
                    'longitude'  => (float)$location['longitude'],
                    'zoom_level' => (int)$location['zoom_level'],
                ];
        }
        Log::debug(sprintf('Enrich with %d locations(s)', count($this->locations)));
    }

    private function collectOpeningBalances(): void
    {
        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($this->user)
            ->setUserGroup($this->userGroup)
            ->setAccounts($this->collection)
            ->withAccountInformation()
            ->setTypes([TransactionTypeEnum::OPENING_BALANCE->value])
        ;
        $journals  = $collector->getExtractedJournals();
        foreach ($journals as $journal) {
            $this->openingBalances[(int)$journal['source_account_id']]
                = [
                    'amount' => Steam::negative($journal['amount']),
                    'date'   => $journal['date'],
                ];
            $this->openingBalances[(int)$journal['destination_account_id']]
                = [
                    'amount' => Steam::positive($journal['amount']),
                    'date'   => $journal['date'],
                ];
        }
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

    private function appendCollectedData(): void
    {
        $this->collection = $this->collection->map(function (Account $item) {
            $id                      = (int)$item->id;
            $item->full_account_type = $this->accountTypes[(int)$item->account_type_id] ?? null;
            $meta                    = [
                'currency'               => null,
                'location'               => [
                    'latitude'   => null,
                    'longitude'  => null,
                    'zoom_level' => null,
                ],
                'object_group_id'        => null,
                'object_group_order'     => null,
                'object_group_title'     => null,
                'opening_balance_date'   => null,
                'opening_balance_amount' => null,
                'account_number'         => null,
                'notes'                  => $notes[$id] ?? null,
                'last_activity'          => $this->lastActivities[$id] ?? null,
            ];

            // add object group if available
            if (array_key_exists($id, $this->mappedObjects)) {
                $key                        = $this->mappedObjects[$id];
                $meta['object_group_id']    = $this->objectGroups[$key]['id'];
                $meta['object_group_title'] = $this->objectGroups[$key]['title'];
                $meta['object_group_order'] = $this->objectGroups[$key]['order'];
            }

            // if location, add location:
            if (array_key_exists($id, $this->locations)) {
                $meta['location'] = $this->locations[$id];
            }
            if (array_key_exists($id, $this->meta)) {
                foreach ($this->meta[$id] as $name => $value) {
                    $meta[$name] = $value;
                }
            }
            // also add currency, if present.
            if (array_key_exists('currency_id', $meta)) {
                $currencyId       = (int)$meta['currency_id'];
                $meta['currency'] = $this->currencies[$currencyId];
            }

            if (array_key_exists($id, $this->openingBalances)) {
                $meta['opening_balance_date']   = $this->openingBalances[$id]['date'];
                $meta['opening_balance_amount'] = $this->openingBalances[$id]['amount'];
            }

            // add balances
            // get currencies:
            $currency                = $this->primaryCurrency; // assume primary currency
            if (null !== $meta['currency']) {
                $currency = $meta['currency'];
            }

            // get the current balance:
            $date                    = $this->getDate();
            // $finalBalance            = Steam::finalAccountBalance($item, $date, $this->primaryCurrency, $this->convertToPrimary);
            $finalBalance            = $this->balances[$id];
            Log::debug(sprintf('Call finalAccountBalance(%s) with date/time "%s"', var_export($this->convertToPrimary, true), $date->toIso8601String()), $finalBalance);

            // collect current balances:
            $currentBalance          = Steam::bcround($finalBalance[$currency->code] ?? '0', $currency->decimal_places);
            $openingBalance          = Steam::bcround($meta['opening_balance_amount'] ?? '0', $currency->decimal_places);
            $virtualBalance          = Steam::bcround($account->virtual_balance ?? '0', $currency->decimal_places);
            $debtAmount              = $meta['current_debt'] ?? null;

            // set some pc_ default values to NULL:
            $pcCurrentBalance        = null;
            $pcOpeningBalance        = null;
            $pcVirtualBalance        = null;
            $pcDebtAmount            = null;

            // convert to primary currency if needed:
            if ($this->convertToPrimary && $currency->id !== $this->primaryCurrency->id) {
                Log::debug(sprintf('Convert to primary, from %s to %s', $currency->code, $this->primaryCurrency->code));
                $converter        = new ExchangeRateConverter();
                $pcCurrentBalance = $converter->convert($currency, $this->primaryCurrency, $date, $currentBalance);
                $pcOpeningBalance = $converter->convert($currency, $this->primaryCurrency, $date, $openingBalance);
                $pcVirtualBalance = $converter->convert($currency, $this->primaryCurrency, $date, $virtualBalance);
                $pcDebtAmount     = null === $debtAmount ? null : $converter->convert($currency, $this->primaryCurrency, $date, $debtAmount);
            }
            if ($this->convertToPrimary && $currency->id === $this->primaryCurrency->id) {
                $pcCurrentBalance = $currentBalance;
                $pcOpeningBalance = $openingBalance;
                $pcVirtualBalance = $virtualBalance;
                $pcDebtAmount     = $debtAmount;
            }

            // set opening balance(s) to NULL if the date is null
            if (null === $meta['opening_balance_date']) {
                $openingBalance   = null;
                $pcOpeningBalance = null;
            }
            $meta['balances']        = [
                'current_balance'    => $currentBalance,
                'pc_current_balance' => $pcCurrentBalance,
                'opening_balance'    => $openingBalance,
                'pc_opening_balance' => $pcOpeningBalance,
                'virtual_balance'    => $virtualBalance,
                'pc_virtual_balance' => $pcVirtualBalance,
                'debt_amount'        => $debtAmount,
                'pc_debt_amount'     => $pcDebtAmount,
            ];
            // end add balances
            $item->meta              = $meta;

            return $item;
        });
    }

    private function collectLastActivities(): void
    {
        $this->lastActivities = Steam::getLastActivities($this->ids);
    }

    private function collectBalances(): void
    {
        $this->balances = Steam::accountsBalancesOptimized($this->collection, $this->getDate(), $this->primaryCurrency, $this->convertToPrimary);
    }

    private function collectObjectGroups(): void
    {
        $set    = DB::table('object_groupables')
            ->whereIn('object_groupable_id', $this->ids)
            ->where('object_groupable_type', Account::class)
            ->get(['object_groupable_id', 'object_group_id'])
        ;

        $ids    = array_unique($set->pluck('object_group_id')->toArray());

        foreach ($set as $entry) {
            $this->mappedObjects[(int)$entry->object_groupable_id] = (int)$entry->object_group_id;
        }

        $groups = ObjectGroup::whereIn('id', $ids)->get(['id', 'title', 'order'])->toArray();
        foreach ($groups as $group) {
            $group['id']                           = (int)$group['id'];
            $group['order']                        = (int)$group['order'];
            $this->objectGroups[(int)$group['id']] = $group;
        }
    }

    public function setDate(?Carbon $date): void
    {
        $this->date = $date;
    }

    public function getDate(): Carbon
    {
        if (null === $this->date) {
            return today();
        }

        return $this->date;
    }
}
