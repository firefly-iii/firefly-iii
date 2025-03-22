<?php

/**
 * RecurringRepositoryInterface.php
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

namespace FireflyIII\Repositories\Recurring;

use Carbon\Carbon;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface RecurringRepositoryInterface
 *
 * @method setUserGroup(UserGroup $group)
 * @method getUserGroup()
 * @method getUser()
 * @method checkUserGroupAccess(UserRoleEnum $role)
 * @method setUser(null|Authenticatable|User $user)
 * @method setUserGroupById(int $userGroupId)
 */
interface RecurringRepositoryInterface
{
    public function createdPreviously(Recurrence $recurrence, Carbon $date): bool;

    /**
     * Destroy a recurring transaction.
     */
    public function destroy(Recurrence $recurrence): void;

    /**
     * Destroy all recurring transactions.
     */
    public function destroyAll(): void;

    /**
     * Returns all of the user's recurring transactions.
     */
    public function get(): Collection;

    /**
     * Get ALL recurring transactions.
     */
    public function getAll(): Collection;

    /**
     * Get the category from a recurring transaction transaction.
     */
    public function getBillId(RecurrenceTransaction $recTransaction): ?int;

    /**
     * Get the budget ID from a recurring transaction transaction.
     */
    public function getBudget(RecurrenceTransaction $recTransaction): ?int;

    /**
     * Get the category from a recurring transaction transaction.
     */
    public function getCategoryId(RecurrenceTransaction $recTransaction): ?int;

    /**
     * Get the category from a recurring transaction transaction.
     */
    public function getCategoryName(RecurrenceTransaction $recTransaction): ?string;

    /**
     * Returns the count of journals created for this recurrence, possibly limited by time.
     */
    public function getJournalCount(Recurrence $recurrence, ?Carbon $start = null, ?Carbon $end = null): int;

    /**
     * Get journal ID's for journals created by this recurring transaction.
     */
    public function getJournalIds(Recurrence $recurrence): array;

    /**
     * Get the notes.
     */
    public function getNoteText(Recurrence $recurrence): string;

    /**
     * Generate events in the date range.
     */
    public function getOccurrencesInRange(RecurrenceRepetition $repetition, Carbon $start, Carbon $end): array;

    public function getPiggyBank(RecurrenceTransaction $transaction): ?int;

    /**
     * Get the tags from the recurring transaction.
     */
    public function getTags(RecurrenceTransaction $transaction): array;

    public function getTransactionPaginator(Recurrence $recurrence, int $page, int $pageSize): LengthAwarePaginator;

    public function getTransactions(Recurrence $recurrence): Collection;

    /**
     * Calculate the next X iterations starting on the date given in $date.
     * Returns an array of Carbon objects.
     *
     * @throws FireflyException
     */
    public function getXOccurrences(RecurrenceRepetition $repetition, Carbon $date, int $count): array;

    /**
     * Calculate the next X iterations starting on the date given in $date.
     * Returns an array of Carbon objects.
     *
     * Only returns them of they are after $afterDate
     *
     * @throws FireflyException
     */
    public function getXOccurrencesSince(RecurrenceRepetition $repetition, Carbon $date, Carbon $afterDate, int $count): array;

    /**
     * Parse the repetition in a string that is user readable.
     */
    public function repetitionDescription(RecurrenceRepetition $repetition): string;

    public function searchRecurrence(string $query, int $limit): Collection;

    /**
     * Store a new recurring transaction.
     *
     * @throws FireflyException
     */
    public function store(array $data): Recurrence;

    /**
     * Calculate how many transactions are to be expected from this recurrence.
     */
    public function totalTransactions(Recurrence $recurrence, RecurrenceRepetition $repetition): int;

    /**
     * Update a recurring transaction.
     */
    public function update(Recurrence $recurrence, array $data): Recurrence;
}
