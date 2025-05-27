<?php

/**
 * LinkTypeRepository.php
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

namespace FireflyIII\Repositories\LinkType;

use FireflyIII\Events\DestroyedTransactionLink;
use FireflyIII\Models\LinkType;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;
use Exception;

/**
 * Class LinkTypeRepository.
 */
class LinkTypeRepository implements LinkTypeRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    public function countJournals(LinkType $linkType): int
    {
        return $linkType->transactionJournalLinks()->count();
    }

    public function destroy(LinkType $linkType, ?LinkType $moveTo = null): bool
    {
        if (null !== $moveTo) {
            TransactionJournalLink::where('link_type_id', $linkType->id)->update(['link_type_id' => $moveTo->id]);
        }
        $linkType->delete();

        return true;
    }

    public function update(LinkType $linkType, array $data): LinkType
    {
        if (array_key_exists('name', $data) && '' !== (string) $data['name']) {
            $linkType->name = $data['name'];
        }
        if (array_key_exists('inward', $data) && '' !== (string) $data['inward']) {
            $linkType->inward = $data['inward'];
        }
        if (array_key_exists('outward', $data) && '' !== (string) $data['outward']) {
            $linkType->outward = $data['outward'];
        }
        $linkType->save();

        return $linkType;
    }

    /**
     * @throws Exception
     */
    public function destroyLink(TransactionJournalLink $link): bool
    {
        event(new DestroyedTransactionLink($link));
        $link->delete();

        return true;
    }

    /**
     * Check if link exists between journals.
     */
    public function findLink(TransactionJournal $one, TransactionJournal $two): bool
    {
        app('log')->debug(sprintf('Now in findLink(%d, %d)', $one->id, $two->id));
        $count         = TransactionJournalLink::whereDestinationId($one->id)->whereSourceId($two->id)->count();
        $opposingCount = TransactionJournalLink::whereDestinationId($two->id)->whereSourceId($one->id)->count();

        return $count + $opposingCount > 0;
    }

    /**
     * Return array of all journal ID's for this type of link.
     */
    public function getJournalIds(LinkType $linkType): array
    {
        $links        = $linkType->transactionJournalLinks()->get(['source_id', 'destination_id']);
        $sources      = $links->pluck('source_id')->toArray();
        $destinations = $links->pluck('destination_id')->toArray();

        return array_unique(array_merge($sources, $destinations));
    }

    public function get(): Collection
    {
        return LinkType::orderBy('name', 'ASC')->get();
    }

    /**
     * Returns all the journal links (of a specific type).
     */
    public function getJournalLinks(?LinkType $linkType = null): Collection
    {
        $query = TransactionJournalLink::with(['source', 'destination'])
            ->leftJoin('transaction_journals as source_journals', 'journal_links.source_id', '=', 'source_journals.id')
            ->leftJoin('transaction_journals as dest_journals', 'journal_links.destination_id', '=', 'dest_journals.id')
            ->where('source_journals.user_id', $this->user->id)
            ->where('dest_journals.user_id', $this->user->id)
            ->whereNull('source_journals.deleted_at')
            ->whereNull('dest_journals.deleted_at')
        ;

        if (null !== $linkType) {
            $query->where('journal_links.link_type_id', $linkType->id);
        }

        return $query->get(['journal_links.*']);
    }

    public function getLink(TransactionJournal $one, TransactionJournal $two): ?TransactionJournalLink
    {
        $left = TransactionJournalLink::whereDestinationId($one->id)->whereSourceId($two->id)->first();
        if (null !== $left) {
            return $left;
        }

        return TransactionJournalLink::whereDestinationId($two->id)->whereSourceId($one->id)->first();
    }

    /**
     * Return list of existing connections.
     */
    public function getLinks(TransactionJournal $journal): Collection
    {
        $outward = TransactionJournalLink::whereSourceId($journal->id)->get();
        $inward  = TransactionJournalLink::whereDestinationId($journal->id)->get();
        $merged  = $outward->merge($inward);

        return $merged->filter(
            static fn (TransactionJournalLink $link) => null !== $link->source && null !== $link->destination
        );
    }

    public function store(array $data): LinkType
    {
        $linkType           = new LinkType();
        $linkType->name     = $data['name'];
        $linkType->inward   = $data['inward'];
        $linkType->outward  = $data['outward'];
        $linkType->editable = true;
        $linkType->save();

        return $linkType;
    }

    /**
     * Store link between two journals.
     *
     * @throws Exception
     */
    public function storeLink(array $information, TransactionJournal $inward, TransactionJournal $outward): ?TransactionJournalLink
    {
        $linkType = $this->find((int) ($information['link_type_id'] ?? 0));

        if (null === $linkType) {
            $linkType = $this->findByName($information['link_type_name']);
        }

        if (null === $linkType) {
            return null;
        }

        // might exist already:
        $existing = $this->findSpecificLink($linkType, $inward, $outward);
        if (null !== $existing) {
            return $existing;
        }

        $link     = new TransactionJournalLink();
        $link->linkType()->associate($linkType);
        if ('inward' === $information['direction']) {
            app('log')->debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->inward, $inward->id, $outward->id));
            $link->source()->associate($inward);
            $link->destination()->associate($outward);
        }

        if ('outward' === $information['direction']) {
            app('log')->debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->outward, $outward->id, $inward->id));
            $link->source()->associate($outward);
            $link->destination()->associate($inward);
        }
        $link->save();

        // make note in noteable:
        $this->setNoteText($link, (string) $information['notes']);

        return $link;
    }

    public function find(int $linkTypeId): ?LinkType
    {
        return LinkType::find($linkTypeId);
    }

    public function findByName(?string $name = null): ?LinkType
    {
        if (null === $name) {
            return null;
        }

        return LinkType::where('name', $name)->first();
    }

    /**
     * See if such a link already exists (and get it).
     */
    public function findSpecificLink(LinkType $linkType, TransactionJournal $inward, TransactionJournal $outward): ?TransactionJournalLink
    {
        return TransactionJournalLink::where('link_type_id', $linkType->id)
            ->where('source_id', $inward->id)
            ->where('destination_id', $outward->id)->first()
        ;
    }

    /**
     * @throws Exception
     */
    private function setNoteText(TransactionJournalLink $link, string $text): void
    {
        $dbNote = $link->notes()->first();
        if ('' !== $text) {
            if (null === $dbNote) {
                $dbNote = new Note();
                $dbNote->noteable()->associate($link);
            }
            $dbNote->text = trim($text);
            $dbNote->save();

            return;
        }
        $dbNote?->delete();
    }

    public function switchLinkById(int $linkId): bool
    {
        /** @var null|TransactionJournalLink $link */
        $link = TransactionJournalLink::find($linkId);
        if (null !== $link && $link->source->user->id === $this->user->id) {
            $this->switchLink($link);
        }

        return true;
    }

    public function switchLink(TransactionJournalLink $link): bool
    {
        $source               = $link->source_id;
        $link->source_id      = $link->destination_id;
        $link->destination_id = $source;
        $link->save();

        return true;
    }

    /**
     * Update an existing transaction journal link.
     *
     * @throws Exception
     */
    public function updateLink(TransactionJournalLink $journalLink, array $data): TransactionJournalLink
    {
        $journalLink->source_id      = $data['inward_id'] ?? $journalLink->source_id;
        $journalLink->destination_id = $data['outward_id'] ?? $journalLink->destination_id;
        $journalLink->save();
        if (array_key_exists('link_type_name', $data)) {
            $linkType = LinkType::whereName($data['link_type_name'])->first();
            if (null !== $linkType) {
                $journalLink->link_type_id = $linkType->id;
                $journalLink->save();
            }
            $journalLink->refresh();
        }

        $journalLink->link_type_id   = $data['link_type_id'] ?? $journalLink->link_type_id;
        $journalLink->save();
        if (array_key_exists('notes', $data) && null !== $data['notes']) {
            $this->setNoteText($journalLink, $data['notes']);
        }

        return $journalLink;
    }
}
