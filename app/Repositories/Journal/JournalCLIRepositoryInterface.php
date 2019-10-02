<?php
/**
 * JournalCLIRepositoryInterface.php
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

namespace FireflyIII\Repositories\Journal;

use Carbon\Carbon;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface JournalCLIRepositoryInterface
 */
interface JournalCLIRepositoryInterface
{
    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * Return all tags as strings in an array.
     *
     * @param TransactionJournal $journal
     *
     * @return array
     */
    public function getTags(TransactionJournal $journal): array;

    /**
     * Returns all journals with more than 2 transactions. Should only return empty collections
     * in Firefly III > v4.8,0.
     *
     * @return Collection
     */
    public function getSplitJournals(): Collection;

    /**
     * Return text of a note attached to journal, or NULL
     *
     * @param TransactionJournal $journal
     *
     * @return string|null
     */
    public function getNoteText(TransactionJournal $journal): ?string;

    /**
     * Return value of a meta field (or NULL).
     *
     * @param TransactionJournal $journal
     * @param string             $field
     *
     * @return null|string
     */
    public function getMetaField(TransactionJournal $journal, string $field): ?string;

    /**
     * Return Carbon value of a meta field (or NULL).
     *
     * @param TransactionJournal $journal
     * @param string             $field
     *
     * @return null|Carbon
     */
    public function getMetaDate(TransactionJournal $journal, string $field): ?Carbon;

    /**
     * Return all journals without a group, used in an upgrade routine.
     *
     * @return array
     */
    public function getJournalsWithoutGroup(): array;

    /**
     * Get all transaction journals with a specific type, regardless of user.
     *
     * @param array $types
     *
     * @return Collection
     */
    public function getAllJournals(array $types): Collection;

    /**
     * Return the ID of the budget linked to the journal (if any) or the transactions (if any).
     *
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function getJournalBudgetId(TransactionJournal $journal): int;

    /**
     * Return the ID of the category linked to the journal (if any) or to the transactions (if any).
     *
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function getJournalCategoryId(TransactionJournal $journal): int;

}
