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

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Location;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Steam;
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
    private array               $accountIds;
    private array               $accountTypeIds;
    private array               $accountTypes;
    private Collection          $collection;
    private array               $currencies;
    private array               $locations;
    private array               $meta;
    private TransactionCurrency $native;
    private array               $notes;
    private array               $openingBalances;
    private User                $user;
    private UserGroup           $userGroup;
    private array               $lastActivities;

    public function __construct()
    {
        $this->accountIds      = [];
        $this->openingBalances = [];
        $this->currencies      = [];
        $this->accountTypeIds  = [];
        $this->accountTypes    = [];
        $this->meta            = [];
        $this->notes           = [];
        $this->lastActivities  = [];
        $this->locations       = [];
        //        $this->repository         = app(AccountRepositoryInterface::class);
        //        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        //        $this->start              = null;
        //        $this->end                = null;
    }

    #[\Override]
    public function enrichSingle(array | Model $model): Account | array
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
        $this->collectNotes();
        $this->collectLastActivities();
        $this->collectLocations();
        $this->collectOpeningBalances();
        $this->appendCollectedData();

        return $this->collection;
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

    private function getAccountTypes(): void
    {
        $types = AccountType::whereIn('id', $this->accountTypeIds)->get();

        /** @var AccountType $type */
        foreach ($types as $type) {
            $this->accountTypes[(int) $type->id] = $type->type;
        }
    }

    private function collectMetaData(): void
    {
        $set = AccountMeta::whereIn('name', ['is_multi_currency', 'include_net_worth', 'currency_id', 'account_role', 'account_number', 'BIC', 'liability_direction', 'interest', 'interest_period', 'current_debt'])
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
        $this->currencies[0] = $this->native;
        foreach ($this->currencies as $id => $currency) {
            if (true === $currency) {
                throw new FireflyException(sprintf('Currency #%d not found.', $id));
            }
        }
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->accountIds)
                     ->whereNotNull('notes.text')
                     ->where('notes.text', '!=', '')
                     ->where('noteable_type', Account::class)->get(['notes.noteable_id', 'notes.text'])->toArray();
        foreach ($notes as $note) {
            $this->notes[(int) $note['noteable_id']] = (string) $note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    private function collectLocations(): void
    {
        $locations = Location::query()->whereIn('locatable_id', $this->accountIds)
                             ->where('locatable_type', Account::class)->get(['locations.locatable_id', 'locations.latitude', 'locations.longitude', 'locations.zoom_level'])->toArray();
        foreach ($locations as $location) {
            $this->locations[(int) $location['locatable_id']]
                = [
                'latitude'   => (float) $location['latitude'],
                'longitude'  => (float) $location['longitude'],
                'zoom_level' => (int) $location['zoom_level'],
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
            ->setTypes([TransactionTypeEnum::OPENING_BALANCE->value]);
        $journals = $collector->getExtractedJournals();
        foreach ($journals as $journal) {
            $this->openingBalances[(int) $journal['source_account_id']]
                = [
                'amount' => Steam::negative($journal['amount']),
                'date'   => $journal['date'],
            ];
            $this->openingBalances[(int) $journal['destination_account_id']]
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
        $accountTypes     = $this->accountTypes;
        $meta             = $this->meta;
        $currencies       = $this->currencies;
        $notes            = $this->notes;
        $openingBalances  = $this->openingBalances;
        $locations        = $this->locations;
        $lastActivities = $this->lastActivities;
        $this->collection = $this->collection->map(function (Account $item) use ($accountTypes, $meta, $currencies, $notes, $openingBalances, $locations, $lastActivities) {
            $item->full_account_type = $accountTypes[(int) $item->account_type_id] ?? null;
            $accountMeta             = [
                'currency' => null,
                'location' => [
                    'latitude'   => null,
                    'longitude'  => null,
                    'zoom_level' => null,
                ],
            ];
            if (array_key_exists((int) $item->id, $meta)) {
                foreach ($meta[(int) $item->id] as $name => $value) {
                    $accountMeta[$name] = $value;
                }
            }
            // also add currency, if present.
            if (array_key_exists('currency_id', $accountMeta)) {
                $currencyId              = (int) $accountMeta['currency_id'];
                $accountMeta['currency'] = $currencies[$currencyId];
            }

            // if notes, add notes.
            if (array_key_exists($item->id, $notes)) {
                $accountMeta['notes'] = $notes[$item->id];
            }
            // if opening balance, add opening balance
            if (array_key_exists($item->id, $openingBalances)) {
                $accountMeta['opening_balance_date']   = $openingBalances[$item->id]['date'];
                $accountMeta['opening_balance_amount'] = $openingBalances[$item->id]['amount'];
            }

            // if location, add location:
            if (array_key_exists($item->id, $locations)) {
                $accountMeta['location'] = $locations[$item->id];
            }
            if (array_key_exists($item->id, $lastActivities)) {
                $accountMeta['last_activity'] = $lastActivities[$item->id];
            }
            $item->meta = $accountMeta;

            return $item;
        });
    }

    public function setNative(TransactionCurrency $native): void
    {
        $this->native = $native;
    }

    private function collectLastActivities(): void
    {
        $this->lastActivities = Steam::getLastActivities($this->accountIds);
    }
}
