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
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Interface JournalRepositoryInterface
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface JournalRepositoryInterface
{
    /**
     * Deletes a transaction group.
     */
    public function destroyGroup(TransactionGroup $transactionGroup): void;

    /**
     * Deletes a journal.
     */
    public function destroyJournal(TransactionJournal $journal): void;

    /**
     * Find a specific journal.
     */
    public function find(int $journalId): ?TransactionJournal;

    public function findByType(array $types): Collection;

    /**
     * Get users very first transaction journal.
     */
    public function firstNull(): ?TransactionJournal;

    /**
     * Returns the destination account of the journal.
     *
     * @throws FireflyException
     */
    public function getDestinationAccount(TransactionJournal $journal): Account;

    /**
     * Return total amount of journal. Is always positive.
     */
    public function getJournalTotal(TransactionJournal $journal): string;

    public function getLast(): ?TransactionJournal;

    public function getLinkNoteText(TransactionJournalLink $link): string;

    /**
     * Return Carbon value of a meta field (or NULL).
     */
    public function getMetaDateById(int $journalId, string $field): ?Carbon;

    /**
     * Returns the source account of the journal.
     *
     * @throws FireflyException
     */
    public function getSourceAccount(TransactionJournal $journal): Account;

    /**
     * TODO Maybe to account repository? Do this wen reconcile is API only.
     */
    public function reconcileById(int $journalId): void;

    /**
     * Search in journal descriptions.
     */
    public function searchJournalDescriptions(string $search, int $limit): Collection;

    /**
     * TODO Maybe to account repository? Do this wen reconcile is API only.
     */
    public function unreconcileById(int $journalId): void;

    /**
     * Update budget for a journal.
     */
    public function updateBudget(TransactionJournal $journal, int $budgetId): TransactionJournal;

    /**
     * Update category for a journal.
     */
    public function updateCategory(TransactionJournal $journal, string $category): TransactionJournal;

    /**
     * Update tag(s) for a journal.
     */
    public function updateTags(TransactionJournal $journal, array $tags): TransactionJournal;
}
