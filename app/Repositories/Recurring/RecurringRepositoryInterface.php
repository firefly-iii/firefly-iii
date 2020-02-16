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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;


/**
 * Interface RecurringRepositoryInterface
 *
 */
interface RecurringRepositoryInterface
{
    /**
     * Destroy a recurring transaction.
     *
     * @param Recurrence $recurrence
     */
    public function destroy(Recurrence $recurrence): void;

    /**
     * Returns all of the user's recurring transactions.
     *
     * @return Collection
     */
    public function get(): Collection;

    /**
     * Get ALL recurring transactions.
     *
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * Get the budget ID from a recurring transaction transaction.
     *
     * @param RecurrenceTransaction $recTransaction
     *
     * @return null|int
     */
    public function getBudget(RecurrenceTransaction $recTransaction): ?int;

    /**
     * Get the category from a recurring transaction transaction.
     *
     * @param RecurrenceTransaction $recTransaction
     *
     * @return null|string
     */
    public function getCategory(RecurrenceTransaction $recTransaction): ?string;

    /**
     * Returns the count of journals created for this recurrence, possibly limited by time.
     *
     * @param Recurrence  $recurrence
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return int
     */
    public function getJournalCount(Recurrence $recurrence, Carbon $start = null, Carbon $end = null): int;

    /**
     * Get journal ID's for journals created by this recurring transaction.
     *
     * @param Recurrence $recurrence
     *
     * @return array
     */
    public function getJournalIds(Recurrence $recurrence): array;

    /**
     * Get the notes.
     *
     * @param Recurrence $recurrence
     *
     * @return string
     */
    public function getNoteText(Recurrence $recurrence): string;

    /**
     * Generate events in the date range.
     *
     * @param RecurrenceRepetition $repetition
     * @param Carbon               $start
     * @param Carbon               $end
     *
     * @return array
     */
    public function getOccurrencesInRange(RecurrenceRepetition $repetition, Carbon $start, Carbon $end): array;

    /**
     * @param RecurrenceTransaction $transaction
     * @return int|null
     */
    public function getPiggyBank(RecurrenceTransaction $transaction): ?int;

    /**
     * Get the tags from the recurring transaction.
     *
     * @param RecurrenceTransaction $transaction
     *
     * @return array
     */
    public function getTags(RecurrenceTransaction $transaction): array;

    /**
     * @param Recurrence $recurrence
     * @param int        $page
     * @param int        $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getTransactionPaginator(Recurrence $recurrence, int $page, int $pageSize): LengthAwarePaginator;

    /**
     * @param Recurrence $recurrence
     *
     * @return Collection
     */
    public function getTransactions(Recurrence $recurrence): Collection;

    /**
     * Calculate the next X iterations starting on the date given in $date.
     * Returns an array of Carbon objects.
     *
     * @param RecurrenceRepetition $repetition
     * @param Carbon               $date
     * @param int                  $count
     *
     * @throws FireflyException
     * @return array
     */
    public function getXOccurrences(RecurrenceRepetition $repetition, Carbon $date, int $count): array;

    /**
     * Calculate the next X iterations starting on the date given in $date.
     * Returns an array of Carbon objects.
     *
     * Only returns them of they are after $afterDate
     *
     * @param RecurrenceRepetition $repetition
     * @param Carbon               $date
     * @param Carbon $afterDate
     * @param int                  $count
     *
     * @throws FireflyException
     * @return array
     */
    public function getXOccurrencesSince(RecurrenceRepetition $repetition, Carbon $date,Carbon $afterDate, int $count): array;

    /**
     * Parse the repetition in a string that is user readable.
     *
     * @param RecurrenceRepetition $repetition
     *
     * @return string
     */
    public function repetitionDescription(RecurrenceRepetition $repetition): string;

    /**
     * Set user for in repository.
     *
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * Store a new recurring transaction.
     *
     * @param array $data
     * @throws FireflyException
     * @return Recurrence
     */
    public function store(array $data): Recurrence;

    /**
     * Update a recurring transaction.
     *
     * @param Recurrence $recurrence
     * @param array      $data
     *
     * @return Recurrence
     */
    public function update(Recurrence $recurrence, array $data): Recurrence;

}
