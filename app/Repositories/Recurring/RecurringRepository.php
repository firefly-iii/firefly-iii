<?php
/**
 * RecurringRepository.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\Recurring;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\RecurrenceFactory;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\Note;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceMeta;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Services\Internal\Destroy\RecurrenceDestroyService;
use FireflyIII\Services\Internal\Update\RecurrenceUpdateService;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class RecurringRepository
 */
class RecurringRepository implements RecurringRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Destroy a recurring transaction.
     *
     * @param Recurrence $recurrence
     */
    public function destroy(Recurrence $recurrence): void
    {
        /** @var RecurrenceDestroyService $service */
        $service = app(RecurrenceDestroyService::class);
        $service->destroy($recurrence);
    }

    /**
     * Returns all of the user's recurring transactions.
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->user->recurrences()
                          ->with(['TransactionCurrency', 'TransactionType', 'RecurrenceRepetitions', 'RecurrenceTransactions'])
                          ->orderBy('active', 'DESC')
                          ->orderBy('transaction_type_id', 'ASC')
                          ->orderBy('title', 'ASC')
                          ->get();
    }

    /**
     * Get ALL recurring transactions.
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        // grab ALL recurring transactions:
        return Recurrence
            ::with(['TransactionCurrency', 'TransactionType', 'RecurrenceRepetitions', 'RecurrenceTransactions'])
            ->orderBy('active', 'DESC')
            ->orderBy('title', 'ASC')
            ->get();
    }

    /**
     * Get the budget ID from a recurring transaction transaction.
     *
     * @param RecurrenceTransaction $recurrenceTransaction
     *
     * @return null|int
     */
    public function getBudget(RecurrenceTransaction $recurrenceTransaction): ?int
    {
        $return = 0;
        /** @var RecurrenceTransactionMeta $meta */
        foreach ($recurrenceTransaction->recurrenceTransactionMeta as $meta) {
            if ('budget_id' === $meta->name) {
                $return = (int)$meta->value;
            }
        }

        return 0 === $return ? null : $return;
    }

    /**
     * Get the category from a recurring transaction transaction.
     *
     * @param RecurrenceTransaction $recurrenceTransaction
     *
     * @return null|string
     */
    public function getCategory(RecurrenceTransaction $recurrenceTransaction): ?string
    {
        $return = '';
        /** @var RecurrenceTransactionMeta $meta */
        foreach ($recurrenceTransaction->recurrenceTransactionMeta as $meta) {
            if ('category_name' === $meta->name) {
                $return = (string)$meta->value;
            }
        }

        return '' === $return ? null : $return;
    }

    /**
     * Returns the journals created for this recurrence, possibly limited by time.
     *
     * @param Recurrence  $recurrence
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return Collection
     */
    public function getJournals(Recurrence $recurrence, Carbon $start = null, Carbon $end = null): Collection
    {
        $query = TransactionJournal
            ::leftJoin('journal_meta', 'journal_meta.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transaction_journals.user_id', $recurrence->user_id)
            ->whereNull('transaction_journals.deleted_at')
            ->where('journal_meta.name', 'recurrence_id')
            ->where('journal_meta.data', '"' . $recurrence->id . '"');
        if (null !== $start) {
            $query->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'));
        }
        if (null !== $end) {
            $query->where('transaction_journals.date', '<=', $end->format('Y-m-d 00:00:00'));
        }

        return $query->get(['transaction_journals.*']);
    }

    /**
     * Get the notes.
     *
     * @param Recurrence $recurrence
     *
     * @return string
     */
    public function getNoteText(Recurrence $recurrence): string
    {
        /** @var Note $note */
        $note = $recurrence->notes()->first();
        if (null !== $note) {
            return (string)$note->text;
        }

        return '';
    }

    /**
     * Generate events in the date range.
     *
     * @param RecurrenceRepetition $repetition
     * @param Carbon               $start
     * @param Carbon               $end
     *
     * @throws FireflyException
     *
     * @return array
     */
    public function getOccurrencesInRange(RecurrenceRepetition $repetition, Carbon $start, Carbon $end): array
    {
        $return  = [];
        $mutator = clone $start;
        $mutator->startOfDay();
        $skipMod  = $repetition->repetition_skip + 1;
        $attempts = 0;
        Log::debug(sprintf('Calculating occurrences for rep type "%s"', $repetition->repetition_type));
        Log::debug(sprintf('Mutator is now: %s', $mutator->format('Y-m-d')));
        switch ($repetition->repetition_type) {
            default:
                throw new FireflyException(
                    sprintf('Cannot calculate occurrences for recurring transaction repetition type "%s"', $repetition->repetition_type)
                );
            case 'daily':
                Log::debug('Rep is daily. Start of loop.');
                while ($mutator <= $end) {
                    Log::debug(sprintf('Mutator is now: %s', $mutator->format('Y-m-d')));
                    if (0 === $attempts % $skipMod) {
                        Log::debug(sprintf('Attempts modulo skipmod is zero, include %s', $mutator->format('Y-m-d')));
                        $return[] = clone $mutator;
                    }
                    $mutator->addDay();
                    $attempts++;
                }
                break;
            case 'weekly':
                Log::debug('Rep is weekly.');
                // monday = 1
                // sunday = 7
                $dayOfWeek = (int)$repetition->repetition_moment;
                Log::debug(sprintf('DoW in repetition is %d, in mutator is %d', $dayOfWeek, $mutator->dayOfWeekIso));
                if ($mutator->dayOfWeekIso > $dayOfWeek) {
                    // day has already passed this week, add one week:
                    $mutator->addWeek();
                    Log::debug(sprintf('Jump to next week, so mutator is now: %s', $mutator->format('Y-m-d')));
                }
                // today is wednesday (3), expected is friday (5): add two days.
                // today is friday (5), expected is monday (1), subtract four days.
                Log::debug(sprintf('Mutator is now: %s', $mutator->format('Y-m-d')));
                $dayDifference = $dayOfWeek - $mutator->dayOfWeekIso;
                $mutator->addDays($dayDifference);
                Log::debug(sprintf('Mutator is now: %s', $mutator->format('Y-m-d')));
                while ($mutator <= $end) {
                    if (0 === $attempts % $skipMod && $start->lte($mutator) && $end->gte($mutator)) {
                        Log::debug('Date is in range of start+end, add to set.');
                        $return[] = clone $mutator;
                    }
                    $attempts++;
                    $mutator->addWeek();
                    Log::debug(sprintf('Mutator is now (end of loop): %s', $mutator->format('Y-m-d')));
                }
                break;
            case 'monthly':
                $dayOfMonth = (int)$repetition->repetition_moment;
                Log::debug(sprintf('Day of month in repetition is %d', $dayOfMonth));
                Log::debug(sprintf('Start is %s.', $start->format('Y-m-d')));
                Log::debug(sprintf('End is %s.', $end->format('Y-m-d')));
                if ($mutator->day > $dayOfMonth) {
                    Log::debug('Add a month.');
                    // day has passed already, add a month.
                    $mutator->addMonth();
                }
                Log::debug(sprintf('Start is now %s.', $mutator->format('Y-m-d')));
                Log::debug('Start loop.');
                while ($mutator < $end) {
                    Log::debug(sprintf('Mutator is now %s.', $mutator->format('Y-m-d')));
                    $domCorrected = min($dayOfMonth, $mutator->daysInMonth);
                    Log::debug(sprintf('DoM corrected is %d', $domCorrected));
                    $mutator->day = $domCorrected;
                    Log::debug(sprintf('Mutator is now %s.', $mutator->format('Y-m-d')));
                    Log::debug(sprintf('$attempts %% $skipMod === 0 is %s', var_export(0 === $attempts % $skipMod, true)));
                    Log::debug(sprintf('$start->lte($mutator) is %s', var_export($start->lte($mutator), true)));
                    Log::debug(sprintf('$end->gte($mutator) is %s', var_export($end->gte($mutator), true)));
                    if (0 === $attempts % $skipMod && $start->lte($mutator) && $end->gte($mutator)) {
                        Log::debug(sprintf('ADD %s to return!', $mutator->format('Y-m-d')));
                        $return[] = clone $mutator;
                    }
                    $attempts++;
                    $mutator->endOfMonth()->startOfDay()->addDay();
                }
                break;
            case 'ndom':
                $mutator->startOfMonth();
                // this feels a bit like a cop out but why reinvent the wheel?
                $counters   = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth',];
                $daysOfWeek = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',];
                $parts      = explode(',', $repetition->repetition_moment);
                while ($mutator <= $end) {
                    $string    = sprintf('%s %s of %s %s', $counters[$parts[0]], $daysOfWeek[$parts[1]], $mutator->format('F'), $mutator->format('Y'));
                    $newCarbon = new Carbon($string);
                    $return[]  = clone $newCarbon;
                    $mutator->endOfMonth()->addDay();
                }
                break;
            case 'yearly':
                $date       = new Carbon($repetition->repetition_moment);
                $date->year = $mutator->year;
                if ($mutator > $date) {
                    $date->addYear();

                }

                // is $date between $start and $end?
                $obj   = clone $date;
                $count = 0;
                while ($obj <= $end && $obj >= $mutator && $count < 10) {

                    $return[] = clone $obj;
                    $obj->addYears(1);
                    $count++;
                }
                break;
        }
        // filter out all the weekend days:
        $return = $this->filterWeekends($repetition, $return);

        return $return;
    }

    /**
     * Get the tags from the recurring transaction.
     *
     * @param Recurrence $recurrence
     *
     * @return array
     */
    public function getTags(Recurrence $recurrence): array
    {
        $tags = [];
        /** @var RecurrenceMeta $meta */
        foreach ($recurrence->recurrenceMeta as $meta) {
            if ('tags' === $meta->name && '' !== $meta->value) {
                $tags = explode(',', $meta->value);
            }
        }

        return $tags;
    }

    /**
     * @param Recurrence $recurrence
     * @param int        $page
     * @param int        $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getTransactionPaginator(Recurrence $recurrence, int $page, int $pageSize): LengthAwarePaginator
    {
        $journalMeta = TransactionJournalMeta
            ::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_journals.user_id', $this->user->id)
            ->where('name', 'recurrence_id')
            ->where('data', json_encode((string)$recurrence->id))
            ->get()->pluck('transaction_journal_id')->toArray();
        $search      = [];
        foreach ($journalMeta as $journalId) {
            $search[] = ['id' => (int)$journalId];
        }
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($recurrence->user);
        $collector->withOpposingAccount()->setAllAssetAccounts()->withCategoryInformation()->withBudgetInformation()->setLimit($pageSize)->setPage($page);
        // filter on specific journals.
        $collector->removeFilter(InternalTransferFilter::class);
        $collector->setJournals(new Collection($search));

        return $collector->getPaginatedJournals();
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return Collection
     */
    public function getTransactions(Recurrence $recurrence): Collection
    {
        $journalMeta = TransactionJournalMeta
            ::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_journals.user_id', $this->user->id)
            ->where('name', 'recurrence_id')
            ->where('data', json_encode((string)$recurrence->id))
            ->get()->pluck('transaction_journal_id')->toArray();
        $search      = [];
        foreach ($journalMeta as $journalId) {
            $search[] = ['id' => (int)$journalId];
        }
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($recurrence->user);
        $collector->withOpposingAccount()->setAllAssetAccounts()->withCategoryInformation()->withBudgetInformation();
        // filter on specific journals.
        $collector->removeFilter(InternalTransferFilter::class);
        $collector->setJournals(new Collection($search));

        return $collector->getJournals();
    }

    /**
     * Calculate the next X iterations starting on the date given in $date.
     *
     * @param RecurrenceRepetition $repetition
     * @param Carbon               $date
     * @param int                  $count
     *
     * @return array
     * @throws FireflyException
     */
    public function getXOccurrences(RecurrenceRepetition $repetition, Carbon $date, int $count): array
    {
        $return   = [];
        $mutator  = clone $date;
        $skipMod  = $repetition->repetition_skip + 1;
        $total    = 0;
        $attempts = 0;
        switch ($repetition->repetition_type) {
            default:
                throw new FireflyException(
                    sprintf('Cannot calculate occurrences for recurring transaction repetition type "%s"', $repetition->repetition_type)
                );
            case 'daily':
                while ($total < $count) {
                    $mutator->addDay();
                    if (0 === $attempts % $skipMod) {
                        $return[] = clone $mutator;
                        $total++;
                    }
                    $attempts++;
                }
                break;
            case 'weekly':
                // monday = 1
                // sunday = 7
                $mutator->addDay(); // always assume today has passed.
                $dayOfWeek = (int)$repetition->repetition_moment;
                if ($mutator->dayOfWeekIso > $dayOfWeek) {
                    // day has already passed this week, add one week:
                    $mutator->addWeek();
                }
                // today is wednesday (3), expected is friday (5): add two days.
                // today is friday (5), expected is monday (1), subtract four days.
                $dayDifference = $dayOfWeek - $mutator->dayOfWeekIso;
                $mutator->addDays($dayDifference);

                while ($total < $count) {
                    if (0 === $attempts % $skipMod) {
                        $return[] = clone $mutator;
                        $total++;
                    }
                    $attempts++;
                    $mutator->addWeek();
                }
                break;
            case 'monthly':
                $mutator->addDay(); // always assume today has passed.
                $dayOfMonth = (int)$repetition->repetition_moment;
                if ($mutator->day > $dayOfMonth) {
                    // day has passed already, add a month.
                    $mutator->addMonth();
                }

                while ($total < $count) {
                    $domCorrected = min($dayOfMonth, $mutator->daysInMonth);
                    $mutator->day = $domCorrected;
                    if (0 === $attempts % $skipMod) {
                        $return[] = clone $mutator;
                        $total++;
                    }
                    $attempts++;
                    $mutator->endOfMonth()->addDay();
                }
                break;
            case 'ndom':
                $mutator->addDay(); // always assume today has passed.
                $mutator->startOfMonth();
                // this feels a bit like a cop out but why reinvent the wheel?
                $counters   = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth',];
                $daysOfWeek = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',];
                $parts      = explode(',', $repetition->repetition_moment);

                while ($total < $count) {
                    $string    = sprintf('%s %s of %s %s', $counters[$parts[0]], $daysOfWeek[$parts[1]], $mutator->format('F'), $mutator->format('Y'));
                    $newCarbon = new Carbon($string);
                    if (0 === $attempts % $skipMod) {
                        $return[] = clone $newCarbon;
                        $total++;
                    }
                    $attempts++;
                    $mutator->endOfMonth()->addDay();
                }
                break;
            case 'yearly':
                $date       = new Carbon($repetition->repetition_moment);
                $date->year = $mutator->year;
                if ($mutator > $date) {
                    $date->addYear();
                }
                $obj = clone $date;
                while ($total < $count) {
                    if (0 === $attempts % $skipMod) {
                        $return[] = clone $obj;
                        $total++;
                    }
                    $obj->addYears(1);
                    $attempts++;
                }
                break;
        }
        // filter out all the weekend days:
        $return = $this->filterWeekends($repetition, $return);

        return $return;
    }

    /**
     * Parse the repetition in a string that is user readable.
     *
     * @param RecurrenceRepetition $repetition
     *
     * @return string
     * @throws FireflyException
     */
    public function repetitionDescription(RecurrenceRepetition $repetition): string
    {
        /** @var Preference $pref */
        $pref     = app('preferences')->getForUser($this->user, 'language', config('firefly.default_language', 'en_US'));
        $language = $pref->data;
        switch ($repetition->repetition_type) {
            default:
                throw new FireflyException(sprintf('Cannot translate recurring transaction repetition type "%s"', $repetition->repetition_type));
                break;
            case 'daily':
                return (string)trans('firefly.recurring_daily', [], $language);
                break;
            case 'weekly':
                $dayOfWeek = trans(sprintf('config.dow_%s', $repetition->repetition_moment), [], $language);

                return (string)trans('firefly.recurring_weekly', ['weekday' => $dayOfWeek], $language);
                break;
            case 'monthly':
                // format a date:
                return (string)trans('firefly.recurring_monthly', ['dayOfMonth' => $repetition->repetition_moment], $language);
                break;
            case 'ndom':
                $parts = explode(',', $repetition->repetition_moment);
                // first part is number of week, second is weekday.
                $dayOfWeek = trans(sprintf('config.dow_%s', $parts[1]), [], $language);

                return (string)trans('firefly.recurring_ndom', ['weekday' => $dayOfWeek, 'dayOfMonth' => $parts[0]], $language);
                break;
            case 'yearly':
                //
                $today = new Carbon;
                $today->endOfYear();
                $repDate     = Carbon::createFromFormat('Y-m-d', $repetition->repetition_moment);
                $diffInYears = $today->diffInYears($repDate);
                $repDate->addYears($diffInYears); // technically not necessary.
                $string = $repDate->formatLocalized(trans('config.month_and_day_no_year'));

                return (string)trans('firefly.recurring_yearly', ['date' => $string], $language);
                break;

        }

    }

    /**
     * Set user for in repository.
     *
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Recurrence
     */
    public function store(array $data): Recurrence
    {
        $factory = new RecurrenceFactory;
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    /**
     * Update a recurring transaction.
     *
     * @param Recurrence $recurrence
     * @param array      $data
     *
     * @return Recurrence
     * @throws FireflyException
     */
    public function update(Recurrence $recurrence, array $data): Recurrence
    {
        /** @var RecurrenceUpdateService $service */
        $service = app(RecurrenceUpdateService::class);

        return $service->update($recurrence, $data);
    }

    /**
     * Filters out all weekend entries, if necessary.
     *
     * @param RecurrenceRepetition $repetition
     * @param array                $dates
     *
     * @return array
     */
    protected function filterWeekends(RecurrenceRepetition $repetition, array $dates): array
    {
        if ((int)$repetition->weekend === RecurrenceRepetition::WEEKEND_DO_NOTHING) {
            Log::debug('Repetition will not be filtered on weekend days.');

            return $dates;
        }
        $return = [];
        /** @var Carbon $date */
        foreach ($dates as $date) {
            $isWeekend = $date->isWeekend();
            if (!$isWeekend) {
                $return[] = clone $date;
                Log::debug(sprintf('Date is %s, not a weekend date.', $date->format('D d M Y')));
                continue;
            }

            // is weekend and must set back to Friday?
            if ($repetition->weekend === RecurrenceRepetition::WEEKEND_TO_FRIDAY) {
                $clone = clone $date;
                $clone->addDays(5 - $date->dayOfWeekIso);
                Log::debug(
                    sprintf('Date is %s, and this is in the weekend, so corrected to %s (Friday).', $date->format('D d M Y'), $clone->format('D d M Y'))
                );
                $return[] = clone $clone;
                continue;
            }

            // postpone to Monday?
            if ($repetition->weekend === RecurrenceRepetition::WEEKEND_TO_MONDAY) {
                $clone = clone $date;
                $clone->addDays(8 - $date->dayOfWeekIso);
                Log::debug(
                    sprintf('Date is %s, and this is in the weekend, so corrected to %s (Monday).', $date->format('D d M Y'), $clone->format('D d M Y'))
                );
                $return[] = $clone;
                continue;
            }
            Log::debug(sprintf('Date is %s, removed from final result', $date->format('D d M Y')));
        }

        // filter unique dates
        Log::debug(sprintf('Count before filtering: %d', \count($dates)));
        $collection = new Collection($return);
        $filtered   = $collection->unique();
        $return     = $filtered->toArray();

        Log::debug(sprintf('Count after filtering: %d', \count($return)));

        return $return;
    }
}
