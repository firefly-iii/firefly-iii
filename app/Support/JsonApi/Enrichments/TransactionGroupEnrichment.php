<?php

/*
 * TransactionGroupEnrichment.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Location;
use FireflyIII\Models\Note;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\UserGroup;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Override;

class TransactionGroupEnrichment implements EnrichmentInterface
{
    private array          $attachmentCount;
    private Collection     $collection;
    private readonly array $dateFields;
    private array          $journalIds;
    private array          $locations;
    private array          $metaData;      // @phpstan-ignore-line
    private array          $notes; // @phpstan-ignore-line
    private array          $tags;
    private User           $user;
    private TransactionCurrency $nativeCurrency;
    private UserGroup      $userGroup;

    public function __construct()
    {
        $this->notes           = [];
        $this->journalIds      = [];
        $this->tags            = [];
        $this->metaData        = [];
        $this->locations       = [];
        $this->attachmentCount = [];
        $this->dateFields      = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date'];
        $this->nativeCurrency   = Amount::getNativeCurrency();
    }

    #[Override]
    public function enrichSingle(array|Model $model): array|TransactionGroup
    {
        Log::debug(__METHOD__);
        if (is_array($model)) {
            $collection = new Collection([$model]);
            $collection = $this->enrich($collection);

            return $collection->first();
        }

        throw new FireflyException('Cannot enrich single model.');
    }

    #[Override]
    public function enrich(Collection $collection): Collection
    {
        Log::debug(sprintf('Now doing account enrichment for %d transaction group(s)', $collection->count()));
        // prep local fields
        $this->collection = $collection;
        $this->collectJournalIds();

        // collect first, then enrich.
        $this->collectNotes();
        $this->collectTags();
        $this->collectMetaData();
        $this->collectLocations();
        $this->collectAttachmentCount();
        $this->appendCollectedData();

        return $this->collection;
    }

    private function collectJournalIds(): void
    {
        /** @var array $group */
        foreach ($this->collection as $group) {
            foreach ($group['transactions'] as $journal) {
                $this->journalIds[] = $journal['transaction_journal_id'];
            }
        }
        $this->journalIds = array_unique($this->journalIds);
    }

    private function collectNotes(): void
    {
        $notes = Note::query()->whereIn('noteable_id', $this->journalIds)
            ->whereNotNull('notes.text')
            ->where('notes.text', '!=', '')
            ->where('noteable_type', TransactionJournal::class)->get(['notes.noteable_id', 'notes.text'])->toArray()
        ;
        foreach ($notes as $note) {
            $this->notes[(int) $note['noteable_id']] = (string) $note['text'];
        }
        Log::debug(sprintf('Enrich with %d note(s)', count($this->notes)));
    }

    private function collectTags(): void
    {
        $set = Tag::leftJoin('tag_transaction_journal', 'tags.id', '=', 'tag_transaction_journal.tag_id')
            ->whereIn('tag_transaction_journal.transaction_journal_id', $this->journalIds)
            ->get(['tag_transaction_journal.transaction_journal_id', 'tags.tag'])->toArray()
        ;
        foreach ($set as $item) {
            $journalId                = $item['transaction_journal_id'];
            $this->tags[$journalId] ??= [];
            $this->tags[$journalId][] = $item['tag'];
        }
    }

    private function collectMetaData(): void
    {
        $set = TransactionJournalMeta::whereIn('transaction_journal_id', $this->journalIds)->get(['transaction_journal_id', 'name', 'data'])->toArray();
        foreach ($set as $entry) {
            $name                                                          = $entry['name'];
            $data                                                          = (string) $entry['data'];
            if ('' === $data) {
                continue;
            }
            if (in_array($name, $this->dateFields, true)) {
                $this->metaData[$entry['transaction_journal_id']][$name] = Carbon::parse($data);

                continue;
            }
            $this->metaData[(int) $entry['transaction_journal_id']][$name] = $data;
        }
    }

    private function collectLocations(): void
    {
        $locations = Location::query()->whereIn('locatable_id', $this->journalIds)
            ->where('locatable_type', TransactionJournal::class)->get(['locations.locatable_id', 'locations.latitude', 'locations.longitude', 'locations.zoom_level'])->toArray()
        ;
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

    private function collectAttachmentCount(): void
    {
        // select count(id) as nr_of_attachments, attachable_id from attachments
        // group by attachable_id
        $attachments = Attachment::query()
            ->whereIn('attachable_id', $this->journalIds)
            ->where('attachable_type', TransactionJournal::class)
            ->groupBy('attachable_id')
            ->get(['attachable_id', DB::raw('COUNT(id) as nr_of_attachments')]) // @phpstan-ignore-line
            ->toArray()
        ;
        foreach ($attachments as $row) {
            $this->attachmentCount[(int) $row['attachable_id']] = (int) $row['nr_of_attachments'];
        }
    }

    private function appendCollectedData(): void
    {
        $notes            = $this->notes;
        $tags             = $this->tags;
        $metaData         = $this->metaData;
        $locations        = $this->locations;
        $attachmentCount  = $this->attachmentCount;
        $nativeCurrency = $this->nativeCurrency;

        $this->collection = $this->collection->map(function (array $item) use ($nativeCurrency, $notes, $tags, $metaData, $locations, $attachmentCount) {
            foreach ($item['transactions'] as $index => $transaction) {
                $journalId                                        = (int) $transaction['transaction_journal_id'];

                // attach notes if they exist:
                $item['transactions'][$index]['notes']            = array_key_exists($journalId, $notes) ? $notes[$journalId] : null;

                // attach tags if they exist:
                $item['transactions'][$index]['tags']             = array_key_exists($journalId, $tags) ? $tags[$journalId] : [];

                // attachment count
                $item['transactions'][$index]['attachment_count'] = array_key_exists($journalId, $attachmentCount) ? $attachmentCount[$journalId] : 0;

                // default location data
                $item['transactions'][$index]['location']         = [
                    'latitude'   => null,
                    'longitude'  => null,
                    'zoom_level' => null,
                ];

                // native currency
                $item['transactions'][$index]['native_currency']  = [
                    'id'               => (string) $nativeCurrency->id,
                    'code'             => $nativeCurrency->code,
                    'name'             => $nativeCurrency->name,
                    'symbol'           => $nativeCurrency->symbol,
                    'decimal_places'   => $nativeCurrency->decimal_places,
                ];

                // append meta data
                $item['transactions'][$index]['meta']             = [];
                $item['transactions'][$index]['meta_date']        = [];
                if (array_key_exists($journalId, $metaData)) {
                    // loop al meta data:
                    foreach ($metaData[$journalId] as $name => $value) {
                        if (in_array($name, $this->dateFields, true)) {
                            $item['transactions'][$index]['meta_date'][$name] = Carbon::parse($value);

                            continue;
                        }
                        $item['transactions'][$index]['meta'][$name] = $value;
                    }
                }

                // append location data
                if (array_key_exists($journalId, $locations)) {
                    $item['transactions'][$index]['location'] = $locations[$journalId];
                }
            }

            return $item;
        });
    }

    public function setUser(User $user): void
    {
        $this->user      = $user;
        $this->userGroup = $user->userGroup;
    }

    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }
}
