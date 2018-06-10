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
use FireflyIII\Models\Note;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\User;
use Illuminate\Support\Collection;


/**
 *
 * Class RecurringRepository
 */
class RecurringRepository implements RecurringRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Returns all of the user's recurring transactions.
     *
     * @return Collection
     */
    public function getActive(): Collection
    {
        return $this->user->recurrences()->with(['TransactionCurrency', 'TransactionType', 'RecurrenceRepetitions', 'RecurrenceTransactions'])->where(
            'active', 1
        )->get();
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
     * Calculate the next X iterations starting on the date given in $date.
     *
     * @param RecurrenceRepetition $repetition
     * @param Carbon               $date
     * @param int                  $count
     *
     * @return array
     * @throws FireflyException
     */
    public function getOccurrences(RecurrenceRepetition $repetition, Carbon $date, int $count = 5): array
    {
        $return  = [];
        $mutator = clone $date;
        switch ($repetition->repetition_type) {
            default:
                throw new FireflyException(
                    sprintf('Cannot calculate occurrences for recurring transaction repetition type "%s"', $repetition->repetition_type)
                );
            case 'daily':
                for ($i = 0; $i < $count; $i++) {
                    $mutator->addDay();
                    $return[] = clone $mutator;
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
                for ($i = 0; $i < $count; $i++) {
                    $return[] = clone $mutator;
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

                for ($i = 0; $i < $count; $i++) {
                    $domCorrected = min($dayOfMonth, $mutator->daysInMonth);
                    $mutator->day = $domCorrected;
                    $return[]     = clone $mutator;
                    $mutator->endOfMonth()->addDay();
                }
                break;
            case 'ndom':
                $mutator->addDay(); // always assume today has passed.
                $mutator->startOfMonth();
                // this feels a bit like a cop out but why reinvent the wheel?
                $string     = '%s %s of %s %s';
                $counters   = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth',];
                $daysOfWeek = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',];
                $parts      = explode(',', $repetition->repetition_moment);
                for ($i = 0; $i < $count; $i++) {
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
                for ($i = 0; $i < $count; $i++) {
                    $obj = clone $date;
                    $obj->addYears($i);
                    $return[] = $obj;
                }
                break;
        }

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
                return trans('firefly.recurring_daily', [], $language);
                break;
            case 'weekly':
                $dayOfWeek = trans(sprintf('config.dow_%s', $repetition->repetition_moment), [], $language);

                return trans('firefly.recurring_weekly', ['weekday' => $dayOfWeek], $language);
                break;
            case 'monthly':
                // format a date:
                return trans('firefly.recurring_monthly', ['dayOfMonth' => $repetition->repetition_moment], $language);
                break;
            case 'ndom':
                $parts = explode(',', $repetition->repetition_moment);
                // first part is number of week, second is weekday.
                $dayOfWeek = trans(sprintf('config.dow_%s', $parts[1]), [], $language);

                return trans('firefly.recurring_ndom', ['weekday' => $dayOfWeek, 'dayOfMonth' => $parts[0]], $language);
                break;
            case 'yearly':
                //
                $today = new Carbon;
                $today->endOfYear();
                $repDate     = Carbon::createFromFormat('Y-m-d', $repetition->repetition_moment);
                $diffInYears = $today->diffInYears($repDate);
                $repDate->addYears($diffInYears); // technically not necessary.
                $string = $repDate->formatLocalized(trans('config.month_and_day_no_year'));

                return trans('firefly.recurring_yearly', ['date' => $string], $language);
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
}