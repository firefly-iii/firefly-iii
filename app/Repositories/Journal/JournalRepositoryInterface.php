<?php
/**
 * JournalRepositoryInterface.php
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
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface JournalRepositoryInterface.
 */
interface JournalRepositoryInterface
{

    /**
     * TODO maybe create JSON repository?
     *
     * Search in journal descriptions.
     *
     * @param string $search
     * @return Collection
     */
    public function searchJournalDescriptions(string $search): Collection;

    /**
     * Deletes a transaction group.
     *
     * @param TransactionGroup $transactionGroup
     */
    public function destroyGroup(TransactionGroup $transactionGroup): void;

    /**
     * Deletes a journal.
     *
     * @param TransactionJournal $journal
     */
    public function destroyJournal(TransactionJournal $journal): void;


    /**
     * TODO move to import repository.
     *
     * Find a journal by its hash.
     *
     * @param string $hash
     *
     * @return TransactionJournalMeta|null
     */
    public function findByHash(string $hash): ?TransactionJournalMeta;

    /**
     * TODO Refactor to "find".
     * Find a specific journal.
     *
     * @param int $journalId
     *
     * @return TransactionJournal|null
     */
    public function findNull(int $journalId): ?TransactionJournal;

    /**
     * Get users very first transaction journal.
     *
     * @return TransactionJournal|null
     */
    public function firstNull(): ?TransactionJournal;

    /**
     * TODO this method is no longer well-fitted in 4.8,0. Should be refactored and/or removed.
     * Return a list of all destination accounts related to journal.
     *
     * @param TransactionJournal $journal
     * @deprecated
     * @return Collection
     */
    public function getJournalDestinationAccounts(TransactionJournal $journal): Collection;

    /**
     * TODO this method is no longer well-fitted in 4.8,0. Should be refactored and/or removed.
     * Return a list of all source accounts related to journal.
     *
     * @param TransactionJournal $journal
     * @deprecated
     * @return Collection
     */
    public function getJournalSourceAccounts(TransactionJournal $journal): Collection;

    /**
     * Return total amount of journal. Is always positive.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public function getJournalTotal(TransactionJournal $journal): string;

    /**
     * TODO used only in transformer, so only for API use.
     * @param TransactionJournalLink $link
     *
     * @return string
     */
    public function getLinkNoteText(TransactionJournalLink $link): string;


    /**
     * Return Carbon value of a meta field (or NULL).
     *
     * @param int $journalId
     * @param string             $field
     *
     * @return null|Carbon
     */
    public function getMetaDateById(int $journalId, string $field): ?Carbon;





    /**
     * TODO maybe move to account repository?
     *
     * @param int $journalId
     */
    public function reconcileById(int $journalId): void;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * Update budget for a journal.
     *
     * @param TransactionJournal $journal
     * @param int $budgetId
     *
     * @return TransactionJournal
     */
    public function updateBudget(TransactionJournal $journal, int $budgetId): TransactionJournal;

    /**
     * Update category for a journal.
     *
     * @param TransactionJournal $journal
     * @param string $category
     *
     * @return TransactionJournal
     */
    public function updateCategory(TransactionJournal $journal, string $category): TransactionJournal;

    /**
     * Update tag(s) for a journal.
     *
     * @param TransactionJournal $journal
     * @param array $tags
     *
     * @return TransactionJournal
     */
    public function updateTags(TransactionJournal $journal, array $tags): TransactionJournal;
}
