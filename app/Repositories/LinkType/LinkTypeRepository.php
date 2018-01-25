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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\LinkType;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class LinkTypeRepository.
 */
class LinkTypeRepository implements LinkTypeRepositoryInterface
{
    /** @var User */
    private $user;

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
     *
     * @throws \Exception
     */
    public function destroy(LinkType $linkType, LinkType $moveTo): bool
    {
        if (null !== $moveTo->id) {
            TransactionJournalLink::where('link_type_id', $linkType->id)->update(['link_type_id' => $moveTo->id]);
        }
        $linkType->delete();

        return true;
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function destroyLink(TransactionJournalLink $link): bool
    {
        $link->delete();

        return true;
    }

    /**
     * @param int $id
     *
     * @return LinkType
     */
    public function find(int $id): LinkType
    {
        $linkType = LinkType::find($id);
        if (null === $linkType) {
            return new LinkType;
        }

        return $linkType;
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
     * @return Collection
     */
    public function get(): Collection
    {
        return LinkType::orderBy('name', 'ASC')->get();
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

        return $outward->merge($inward);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
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
     * @param TransactionJournal $left
     * @param TransactionJournal $right
     *
     * @return mixed
     * @throws FireflyException
     */
    public function storeLink(array $information, TransactionJournal $left, TransactionJournal $right): TransactionJournalLink
    {
        $linkType = $this->find(intval($information['link_type_id']) ?? 0);
        if (is_null($linkType->id)) {
            throw new FireflyException(sprintf('Link type #%d cannot be resolved to an actual link type', intval($information['link_type_id']) ?? 0));
        }
        $link = new TransactionJournalLink;
        $link->linkType()->associate($linkType);
        if ('inward' === $information['direction']) {
            Log::debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->inward, $left->id, $right->id));
            $link->source()->associate($left);
            $link->destination()->associate($right);
        }

        if ('outward' === $information['direction']) {
            Log::debug(sprintf('Link type is inwards ("%s"), so %d is source and %d is destination.', $linkType->outward, $right->id, $left->id));
            $link->source()->associate($right);
            $link->destination()->associate($left);
        }
        $link->save();

        // make note in noteable:
        if (strlen($information['notes']) > 0) {
            $dbNote = $link->notes()->first();
            if (null === $dbNote) {
                $dbNote = new Note();
                $dbNote->noteable()->associate($link);
            }
            $dbNote->text = trim($information['notes']);
            $dbNote->save();
        }

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
}
