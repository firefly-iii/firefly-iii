<?php
/**
 * LinkTypeRepositoryInterface.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Models\LinkType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface LinkTypeRepositoryInterface.
 */
interface LinkTypeRepositoryInterface
{
    /**
     * @param LinkType $linkType
     *
     * @return int
     */
    public function countJournals(LinkType $linkType): int;

    /**
     * @param LinkType $linkType
     * @param LinkType $moveTo
     *
     * @return bool
     */
    public function destroy(LinkType $linkType, LinkType $moveTo = null): bool;

    /**
     * @param TransactionJournalLink $link
     *
     * @return bool
     */
    public function destroyLink(TransactionJournalLink $link): bool;

    /**
     * Find link type by name.
     *
     * @param string|null $name
     *
     * @return LinkType|null
     */
    public function findByName(string $name = null): ?LinkType;

    /**
     * Check if link exists between journals.
     *
     * @param TransactionJournal $one
     * @param TransactionJournal $two
     *
     * @return bool
     */
    public function findLink(TransactionJournal $one, TransactionJournal $two): bool;

    /**
     * @param int $linkTypeId
     *
     * @return LinkType|null
     */
    public function findNull(int $linkTypeId): ?LinkType;

    /**
     * See if such a link already exists (and get it).
     *
     * @param LinkType           $linkType
     * @param TransactionJournal $inward
     * @param TransactionJournal $outward
     *
     * @return TransactionJournalLink|null
     */
    public function findSpecificLink(LinkType $linkType, TransactionJournal $inward, TransactionJournal $outward): ?TransactionJournalLink;

    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * Return array of all journal ID's for this type of link.
     *
     * @param LinkType $linkType
     *
     * @return array
     */
    public function getJournalIds(LinkType $linkType): array;

    /**
     * @param LinkType|null $linkType
     *
     * @return Collection
     */
    public function getJournalLinks(LinkType $linkType = null): Collection;

    /**
     * Return list of existing connections.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getLinks(TransactionJournal $journal): Collection;

    /**
     * Set the user for this instance.
     *
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * @param array $data
     *
     * @return LinkType
     */
    public function store(array $data): LinkType;

    /**
     * Store link between two journals.
     *
     * @param array              $information
     * @param TransactionJournal $inward
     * @param TransactionJournal $outward
     *
     * @return TransactionJournalLink|null
     */
    public function storeLink(array $information, TransactionJournal $inward, TransactionJournal $outward): ?TransactionJournalLink;

    /**
     * @param TransactionJournalLink $link
     *
     * @return bool
     */
    public function switchLink(TransactionJournalLink $link): bool;

    /**
     * @param LinkType $linkType
     * @param array    $data
     *
     * @return LinkType
     */
    public function update(LinkType $linkType, array $data): LinkType;

    /**
     * Update an existing transaction journal link.
     *
     * @param TransactionJournalLink $journalLink
     * @param array                  $data
     *
     * @return TransactionJournalLink
     */
    public function updateLink(TransactionJournalLink $journalLink, array $data): TransactionJournalLink;
}
