<?php
/**
 * JournalRepositoryInterface.php
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

namespace FireflyIII\Repositories\Journal;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface JournalRepositoryInterface.
 */
interface JournalRepositoryInterface
{
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
     * @param array $types
     *
     * @return Collection
     */
    public function findByType(array $types): Collection;

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
     * Returns the destination account of the journal.
     *
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    public function getDestinationAccount(TransactionJournal $journal): Account;

    /**
     * TODO this method is no longer well-fitted in 4.8,0. Should be refactored and/or removed.
     * Return a list of all destination accounts related to journal.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     * @deprecated
     */
    public function getJournalDestinationAccounts(TransactionJournal $journal): Collection;

    /**
     * TODO this method is no longer well-fitted in 4.8,0. Should be refactored and/or removed.
     * Return a list of all source accounts related to journal.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     * @deprecated
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
     * @return TransactionJournal|null
     */
    public function getLast(): ?TransactionJournal;

    /**
     * TODO used only in transformer, so only for API use.
     *
     * @param TransactionJournalLink $link
     *
     * @return string
     */
    public function getLinkNoteText(TransactionJournalLink $link): string;

    /**
     * Return Carbon value of a meta field (or NULL).
     *
     * @param int    $journalId
     * @param string $field
     *
     * @return null|Carbon
     */
    public function getMetaDateById(int $journalId, string $field): ?Carbon;

    /**
     * Returns the source account of the journal.
     *
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    public function getSourceAccount(TransactionJournal $journal): Account;

    /**
     * TODO maybe move to account repository?
     *
     * @param int $journalId
     */
    public function reconcileById(int $journalId): void;

    /**
     * Search in journal descriptions.
     *
     * @param string $search
     * @param int    $limit
     *
     * @return Collection
     */
    public function searchJournalDescriptions(string $search, int $limit): Collection;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * Update budget for a journal.
     *
     * @param TransactionJournal $journal
     * @param int                $budgetId
     *
     * @return TransactionJournal
     */
    public function updateBudget(TransactionJournal $journal, int $budgetId): TransactionJournal;

    /**
     * Update category for a journal.
     *
     * @param TransactionJournal $journal
     * @param string             $category
     *
     * @return TransactionJournal
     */
    public function updateCategory(TransactionJournal $journal, string $category): TransactionJournal;

    /**
     * Update tag(s) for a journal.
     *
     * @param TransactionJournal $journal
     * @param array              $tags
     *
     * @return TransactionJournal
     */
    public function updateTags(TransactionJournal $journal, array $tags): TransactionJournal;
}
