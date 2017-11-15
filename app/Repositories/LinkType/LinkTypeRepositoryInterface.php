<?php
/**
 * LinkTypeRepositoryInterface.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\LinkType;

use FireflyIII\Models\LinkType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
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
    public function destroy(LinkType $linkType, LinkType $moveTo): bool;

    /**
     * @param TransactionJournalLink $link
     *
     * @return bool
     */
    public function destroyLink(TransactionJournalLink $link): bool;

    /**
     * @param int $id
     *
     * @return LinkType
     */
    public function find(int $id): LinkType;

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
     * @return Collection
     */
    public function get(): Collection;

    /**
     * Return list of existing connections.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getLinks(TransactionJournal $journal): Collection;

    /**
     * @param array $data
     *
     * @return LinkType
     */
    public function store(array $data): LinkType;

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
}
