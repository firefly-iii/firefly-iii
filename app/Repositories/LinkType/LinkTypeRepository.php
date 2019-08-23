<?php
/**
 * LinkTypeRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\LinkType;

use Exception;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\LinkType;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class LinkTypeRepository.
 *
 *
 */
class LinkTypeRepository implements LinkTypeRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param LinkType $linkType
     *
     * @return int
     */
    public function countJournals(LinkType $linkType): int
    {
        return $linkType->transactionJournalLinks()->count();
    }

    /**
     * @param LinkType $linkType
     * @param LinkType $moveTo
     *
     * @return bool
     * @throws \Exception
     */
    public function destroy(LinkType $linkType, LinkType $moveTo = null): bool
    {
        if (null !== $moveTo) {
            TransactionJournalLink::where('link_type_id', $linkType->id)->update(['link_type_id' => $moveTo->id]);
        }
        $linkType->delete();

        return true;
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return bool
     * @throws \Exception
     */
    public function destroyLink(TransactionJournalLink $link): bool
    {
        $link->delete();

        return true;
    }

    /**
     * @param string|null $name
     *
     * @return LinkType|null
     */
    public function findByName(string $name = null): ?LinkType
    {
        if (null === $name) {
            return null;
        }

        return LinkType::where('name', $name)->first();
    }

    /**
     * Check if link exists between journals.
     *
     * @param TransactionJournal $one
     * @param TransactionJournal $two
     *
     * @return bool
     */
    public function findLink(TransactionJournal $one, TransactionJournal $two): bool
    {
        $count         = TransactionJournalLink::whereDestinationId($one->id)->whereSourceId($two->id)->count();
        $opposingCount = TransactionJournalLink::whereDestinationId($two->id)->whereSourceId($one->id)->count();

        return $count + $opposingCount > 0;
    }

    /**
     * @param int $linkTypeId
     *
     * @return LinkType|null
     */
    public function findNull(int $linkTypeId): ?LinkType
    {
        return LinkType::find($linkTypeId);
    }

    /**
     * See if such a link already exists (and get it).
     *
     * @param LinkType           $linkType
     * @param TransactionJournal $inward
     * @param TransactionJournal $outward
     *
     * @return TransactionJournalLink|null
     */
    public function findSpecificLink(LinkType $linkType, TransactionJournal $inward, TransactionJournal $outward): ?TransactionJournalLink
    {
        return TransactionJournalLink
            ::where('link_type_id', $linkType->id)
            ->where('source_id', $inward->id)
            ->where('destination_id', $outward->id)->first();

    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return LinkType::orderBy('name', 'ASC')->get();
    }

    /**
     * Return array of all journal ID's for this type of link.
     *
     * @param LinkType $linkType
     *
     * @return array
     */
    public function getJournalIds(LinkType $linkType): array
    {
        $links        = $linkType->transactionJournalLinks()->get(['source_id', 'destination_id']);
        $sources      = $links->pluck('source_id')->toArray();
        $destinations = $links->pluck('destination_id')->toArray();

        return array_unique(array_merge($sources, $destinations));
    }

    /**
     * Returns all the journal links (of a specific type).
     *
     * @param $linkType
     *
     * @return Collection
     */
    public function getJournalLinks(LinkType $linkType = null): Collection
    {
        $query = TransactionJournalLink
            ::with(['source','destination'])
            ->leftJoin('transaction_journals as source_journals', 'journal_links.source_id', '=', 'source_journals.id')
            ->leftJoin('transaction_journals as dest_journals', 'journal_links.destination_id', '=', 'dest_journals.id')
            ->where('source_journals.user_id', $this->user->id)
            ->where('dest_journals.user_id', $this->user->id)
            ->whereNull('source_journals.deleted_at')
            ->whereNull('dest_journals.deleted_at');

        if (null !== $linkType) {
            $query->where('journal_links.link_type_id', $linkType->id);
        }
       return $query->get(['journal_links.*']);
    }

    /**
     * Return list of existing connections.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getLinks(TransactionJournal $journal): Collection
    {
        $outward = TransactionJournalLink::whereSourceId($journal->id)->get();
        $inward  = TransactionJournalLink::whereDestinationId($journal->id)->get();
        $merged  = $outward->merge($inward);

        $filtered = $merged->filter(
            function (TransactionJournalLink $link) {
                return (null !== $link->source && null !== $link->destination);
            }
        );

        return $filtered;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return LinkType
     */
    public function store(array $data): LinkType
    {
        $linkType           = new LinkType;
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
     * @param array              $information
     * @param TransactionJournal $inward
     * @param TransactionJournal $outward
     *
     * @return TransactionJournalLink|null
     *
     */
    public function storeLink(array $information, TransactionJournal $inward, TransactionJournal $outward): ?TransactionJournalLink
    {
        $linkType = $this->findNull((int)($information['link_type_id'] ?? 0));

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

        $link = new TransactionJournalLink;
        $link->linkType()->associate($linkType);
        if ('inward' === $information['direction']) {
            Log::debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->inward, $inward->id, $outward->id));
            $link->source()->associate($inward);
            $link->destination()->associate($outward);
        }

        if ('outward' === $information['direction']) {
            Log::debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->outward, $outward->id, $inward->id));
            $link->source()->associate($outward);
            $link->destination()->associate($inward);
        }
        $link->save();

        // make note in noteable:
        $this->setNoteText($link, (string)$information['notes']);

        return $link;
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return bool
     */
    public function switchLink(TransactionJournalLink $link): bool
    {
        $source               = $link->source_id;
        $link->source_id      = $link->destination_id;
        $link->destination_id = $source;
        $link->save();

        return true;
    }

    /**
     * @param LinkType $linkType
     * @param array    $data
     *
     * @return LinkType
     */
    public function update(LinkType $linkType, array $data): LinkType
    {
        $linkType->name    = $data['name'];
        $linkType->inward  = $data['inward'];
        $linkType->outward = $data['outward'];
        $linkType->save();

        return $linkType;
    }

    /**
     * Update an existing transaction journal link.
     *
     * @param TransactionJournalLink $journalLink
     * @param array                  $data
     *
     * @return TransactionJournalLink
     */
    public function updateLink(TransactionJournalLink $journalLink, array $data): TransactionJournalLink
    {
        $journalLink->source_id      = $data['inward']->id;
        $journalLink->destination_id = $data['outward']->id;
        $journalLink->link_type_id   = $data['link_type_id'];
        $journalLink->save();
        $this->setNoteText($journalLink, $data['notes']);

        return $journalLink;
    }

    /**
     * @param TransactionJournalLink $link
     * @param string                 $text
     *
     *
     * @throws \Exception
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
        if (null !== $dbNote && '' === $text) {
            try {
                $dbNote->delete();
            } catch (Exception $e) {
                Log::debug(sprintf('Could not delete note: %s', $e->getMessage()));
            }
        }

    }
}
