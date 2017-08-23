<?php
/**
 * LinkTypeRepositoryInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\LinkType;

use FireflyIII\Models\LinkType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use Illuminate\Support\Collection;

/**
 * Interface LinkTypeRepositoryInterface
 *
 * @package FireflyIII\Repositories\LinkType
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
     * @param int $id
     *
     * @return LinkType
     */
    public function find(int $id): LinkType;

    /**
     * @param TransactionJournalLink $link
     *
     * @return bool
     */
    public function destroyLink(TransactionJournalLink $link):bool;

    /**
     * @param TransactionJournalLink $link
     *
     * @return bool
     */
    public function switchLink(TransactionJournalLink $link): bool;

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
     * @param LinkType $linkType
     * @param array    $data
     *
     * @return LinkType
     */
    public function update(LinkType $linkType, array $data): LinkType;

}