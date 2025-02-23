<?php

/**
 * RecurringRepository.php
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
use FireflyIII\Factory\RecurrenceFactory;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
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
use FireflyIII\Support\Repositories\Recurring\CalculateRangeOccurrences;
use FireflyIII\Support\Repositories\Recurring\CalculateXOccurrences;
use FireflyIII\Support\Repositories\Recurring\CalculateXOccurrencesSince;
use FireflyIII\Support\Repositories\Recurring\FiltersWeekends;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class RecurringRepository
 */
class RecurringRepository implements RecurringRepositoryInterface, UserGroupInterface
{
    use CalculateRangeOccurrences;
    use CalculateXOccurrences;
    use CalculateXOccurrencesSince;
    use FiltersWeekends;

    use UserGroupTrait;

    public function createdPreviously(Recurrence $recurrence, Carbon $date): bool
    {
        // if not, loop set and try to read the recurrence_date. If it matches start or end, return it as well.
        $set
            = TransactionJournalMeta::where(static function (Builder $q1) use ($recurrence): void {
                $q1->where('name', 'recurrence_id');
                $q1->where('data', json_encode((string) $recurrence->id));
            })->get(['journal_meta.transaction_journal_id']);

        // there are X journals made for this recurrence. Any of them meant for today?
        foreach ($set as $journalMeta) {
            $count = TransactionJournalMeta::where(static function (Builder $q2) use ($date): void {
                $string = (string) $date;
                app('log')->debug(sprintf('Search for date: %s', json_encode($string)));
                $q2->where('name', 'recurrence_date');
                $q2->where('data', json_encode($string));
            })
                ->where('transaction_journal_id', $journalMeta->transaction_journal_id)
                ->count()
            ;
            if ($count > 0) {
                app('log')->debug(sprintf('Looks like journal #%d was already created', $journalMeta->transaction_journal_id));

                return true;
            }
        }

        return false;
    }

    /**
     * Returns all the user's recurring transactions.
     */
    public function get(): Collection
    {
        return $this->user->recurrences()
            ->with(['TransactionCurrency', 'TransactionType', 'RecurrenceRepetitions', 'RecurrenceTransactions'])
            ->orderBy('active', 'DESC')
            ->orderBy('transaction_type_id', 'ASC')
            ->orderBy('title', 'ASC')
            ->get()
        ;
    }

    /**
     * Destroy a recurring transaction.
     */
    public function destroy(Recurrence $recurrence): void
    {
        /** @var RecurrenceDestroyService $service */
        $service = app(RecurrenceDestroyService::class);
        $service->destroy($recurrence);
    }

    public function destroyAll(): void
    {
        Log::channel('audit')->info('Delete all recurring transactions through destroyAll');
        $this->user->recurrences()->delete();
    }

    /**
     * Get ALL recurring transactions.
     */
    public function getAll(): Collection
    {
        // grab ALL recurring transactions:
        return Recurrence::with(['TransactionCurrency', 'TransactionType', 'RecurrenceRepetitions', 'RecurrenceTransactions'])
            ->orderBy('active', 'DESC')
            ->orderBy('title', 'ASC')
            ->get()
        ;
    }

    public function getBillId(RecurrenceTransaction $recTransaction): ?int
    {
        $return = null;

        /** @var RecurrenceTransactionMeta $meta */
        foreach ($recTransaction->recurrenceTransactionMeta as $meta) {
            if ('bill_id' === $meta->name) {
                $return = (int) $meta->value;
            }
        }

        return $return;
    }

    /**
     * Get the budget ID from a recurring transaction transaction.
     */
    public function getBudget(RecurrenceTransaction $recTransaction): ?int
    {
        $return = 0;

        /** @var RecurrenceTransactionMeta $meta */
        foreach ($recTransaction->recurrenceTransactionMeta as $meta) {
            if ('budget_id' === $meta->name) {
                $return = (int) $meta->value;
            }
        }

        return 0 === $return ? null : $return;
    }

    /**
     * Get the category from a recurring transaction transaction.
     */
    public function getCategoryId(RecurrenceTransaction $recTransaction): ?int
    {
        $return = '';

        /** @var RecurrenceTransactionMeta $meta */
        foreach ($recTransaction->recurrenceTransactionMeta as $meta) {
            if ('category_id' === $meta->name) {
                $return = (int) $meta->value;
            }
        }

        return '' === $return ? null : $return;
    }

    /**
     * Get the category from a recurring transaction transaction.
     */
    public function getCategoryName(RecurrenceTransaction $recTransaction): ?string
    {
        $return = '';

        /** @var RecurrenceTransactionMeta $meta */
        foreach ($recTransaction->recurrenceTransactionMeta as $meta) {
            if ('category_name' === $meta->name) {
                $return = (string) $meta->value;
            }
        }

        return '' === $return ? null : $return;
    }

    /**
     * Returns the journals created for this recurrence, possibly limited by time.
     */
    public function getJournalCount(Recurrence $recurrence, ?Carbon $start = null, ?Carbon $end = null): int
    {
        Log::debug(sprintf('Now in getJournalCount(#%d, "%s", "%s")', $recurrence->id, $start?->format('Y-m-d H:i:s'), $end?->format('Y-m-d H:i:s')));
        $query = TransactionJournal::leftJoin('journal_meta', 'journal_meta.transaction_journal_id', '=', 'transaction_journals.id')
            ->where('transaction_journals.user_id', $recurrence->user_id)
            ->whereNull('transaction_journals.deleted_at')
            ->where('journal_meta.name', 'recurrence_id')
            ->where('journal_meta.data', '"'.$recurrence->id.'"')
        ;
        if (null !== $start) {
            $query->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'));
        }
        if (null !== $end) {
            $query->where('transaction_journals.date', '<=', $end->format('Y-m-d 00:00:00'));
        }
        $count = $query->count('transaction_journals.id');
        Log::debug(sprintf('Count is %d', $count));

        return $count;
    }

    /**
     * Get journal ID's for journals created by this recurring transaction.
     */
    public function getJournalIds(Recurrence $recurrence): array
    {
        return TransactionJournalMeta::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
            ->where('transaction_journals.user_id', $this->user->id)
            ->where('journal_meta.name', '=', 'recurrence_id')
            ->where('journal_meta.data', '=', json_encode((string) $recurrence->id))
            ->get(['journal_meta.transaction_journal_id'])->pluck('transaction_journal_id')->toArray()
        ;
    }

    /**
     * Get the notes.
     */
    public function getNoteText(Recurrence $recurrence): string
    {
        /** @var null|Note $note */
        $note = $recurrence->notes()->first();

        return (string) $note?->text;
    }

    public function getPiggyBank(RecurrenceTransaction $transaction): ?int
    {
        $meta = $transaction->recurrenceTransactionMeta;

        /** @var RecurrenceTransactionMeta $metaEntry */
        foreach ($meta as $metaEntry) {
            if ('piggy_bank_id' === $metaEntry->name) {
                return (int) $metaEntry->value;
            }
        }

        return null;
    }

    /**
     * Get the tags from the recurring transaction.
     */
    public function getTags(RecurrenceTransaction $transaction): array
    {
        $tags = [];

        /** @var RecurrenceMeta $meta */
        foreach ($transaction->recurrenceTransactionMeta as $meta) {
            if ('tags' === $meta->name && '' !== $meta->value) {
                $tags = json_decode($meta->value, true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return $tags;
    }

    public function getTransactionPaginator(Recurrence $recurrence, int $page, int $pageSize): LengthAwarePaginator
    {
        $journalMeta = TransactionJournalMeta::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_journals.user_id', $this->user->id)
            ->where('name', 'recurrence_id')
            ->where('data', json_encode((string) $recurrence->id))
            ->get()->pluck('transaction_journal_id')->toArray()
        ;
        $search      = [];
        foreach ($journalMeta as $journalId) {
            $search[] = (int) $journalId;
        }

        /** @var GroupCollectorInterface $collector */
        $collector   = app(GroupCollectorInterface::class);

        $collector->setUser($recurrence->user);
        $collector->withCategoryInformation()->withBudgetInformation()->setLimit($pageSize)->setPage($page)
            ->withAccountInformation()
        ;
        $collector->setJournalIds($search);

        return $collector->getPaginatedGroups();
    }

    public function getTransactions(Recurrence $recurrence): Collection
    {
        $journalMeta = TransactionJournalMeta::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
            ->whereNull('transaction_journals.deleted_at')
            ->where('transaction_journals.user_id', $this->user->id)
            ->where('name', 'recurrence_id')
            ->where('data', json_encode((string) $recurrence->id))
            ->get()->pluck('transaction_journal_id')->toArray()
        ;
        $search      = [];

        foreach ($journalMeta as $journalId) {
            $search[] = (int) $journalId;
        }
        if (0 === count($search)) {
            return new Collection();
        }

        /** @var GroupCollectorInterface $collector */
        $collector   = app(GroupCollectorInterface::class);

        $collector->setUser($recurrence->user);
        $collector->withCategoryInformation()->withBudgetInformation()->withAccountInformation();
        // filter on specific journals.
        $collector->setJournalIds($search);

        return $collector->getGroups();
    }

    /**
     * Calculate the next X iterations starting on the date given in $date.
     */
    public function getXOccurrences(RecurrenceRepetition $repetition, Carbon $date, int $count): array
    {
        $skipMod     = $repetition->repetition_skip + 1;
        $occurrences = [];
        if ('daily' === $repetition->repetition_type) {
            $occurrences = $this->getXDailyOccurrences($date, $count, $skipMod);
        }
        if ('weekly' === $repetition->repetition_type) {
            $occurrences = $this->getXWeeklyOccurrences($date, $count, $skipMod, $repetition->repetition_moment);
        }
        if ('monthly' === $repetition->repetition_type) {
            $occurrences = $this->getXMonthlyOccurrences($date, $count, $skipMod, $repetition->repetition_moment);
        }
        if ('ndom' === $repetition->repetition_type) {
            $occurrences = $this->getXNDomOccurrences($date, $count, $skipMod, $repetition->repetition_moment);
        }
        if ('yearly' === $repetition->repetition_type) {
            $occurrences = $this->getXYearlyOccurrences($date, $count, $skipMod, $repetition->repetition_moment);
        }

        // filter out all the weekend days:
        return $this->filterWeekends($repetition, $occurrences);
    }

    /**
     * Calculate the next X iterations starting on the date given in $date.
     * Returns an array of Carbon objects.
     *
     * Only returns them of they are after $afterDate
     */
    public function getXOccurrencesSince(RecurrenceRepetition $repetition, Carbon $date, Carbon $afterDate, int $count): array
    {
        app('log')->debug('Now in getXOccurrencesSince()');
        $skipMod     = $repetition->repetition_skip + 1;
        $occurrences = [];

        // to fix #8616, take a few days from both dates, then filter the list to make sure no entries
        // from today or before are saved.
        $date->subDays(4);
        $afterDate->subDays(4);

        if ('daily' === $repetition->repetition_type) {
            $occurrences = $this->getXDailyOccurrencesSince($date, $afterDate, $count, $skipMod);
        }
        if ('weekly' === $repetition->repetition_type) {
            $occurrences = $this->getXWeeklyOccurrencesSince($date, $afterDate, $count, $skipMod, $repetition->repetition_moment);
        }
        if ('monthly' === $repetition->repetition_type) {
            $occurrences = $this->getXMonthlyOccurrencesSince($date, $afterDate, $count, $skipMod, $repetition->repetition_moment);
        }
        if ('ndom' === $repetition->repetition_type) {
            $occurrences = $this->getXNDomOccurrencesSince($date, $afterDate, $count, $skipMod, $repetition->repetition_moment);
        }
        if ('yearly' === $repetition->repetition_type) {
            $occurrences = $this->getXYearlyOccurrencesSince($date, $afterDate, $count, $skipMod, $repetition->repetition_moment);
        }

        // filter out all the weekend days:
        $occurrences = $this->filterWeekends($repetition, $occurrences);

        // filter out everything if "repeat_until" is set.
        $repeatUntil = $repetition->recurrence->repeat_until;

        return $this->filterMaxDate($repeatUntil, $occurrences);
    }

    private function filterMaxDate(?Carbon $max, array $occurrences): array
    {
        $filtered = [];
        if (null === $max) {
            foreach ($occurrences as $date) {
                if ($date->gt(today())) {
                    $filtered[] = $date;
                }
            }

            return $filtered;
        }
        foreach ($occurrences as $date) {
            if ($date->lte($max) && $date->gt(today())) {
                $filtered[] = $date;
            }
        }

        return $filtered;
    }

    /**
     * Parse the repetition in a string that is user readable.
     *
     * @throws FireflyException
     */
    public function repetitionDescription(RecurrenceRepetition $repetition): string
    {
        app('log')->debug('Now in repetitionDescription()');

        /** @var Preference $pref */
        $pref     = app('preferences')->getForUser($this->user, 'language', config('firefly.default_language', 'en_US'));
        $language = $pref->data;
        if (is_array($language)) {
            $language = 'en_US';
        }
        $language = (string) $language;
        if ('daily' === $repetition->repetition_type) {
            return (string) trans('firefly.recurring_daily', [], $language);
        }
        if ('weekly' === $repetition->repetition_type) {
            $dayOfWeek = trans(sprintf('config.dow_%s', $repetition->repetition_moment), [], $language);
            if ($repetition->repetition_skip > 0) {
                return (string) trans('firefly.recurring_weekly_skip', ['weekday' => $dayOfWeek, 'skip' => $repetition->repetition_skip + 1], $language);
            }

            return (string) trans('firefly.recurring_weekly', ['weekday' => $dayOfWeek], $language);
        }
        if ('monthly' === $repetition->repetition_type) {
            if ($repetition->repetition_skip > 0) {
                return (string) trans(
                    'firefly.recurring_monthly_skip',
                    ['dayOfMonth' => $repetition->repetition_moment, 'skip' => $repetition->repetition_skip + 1],
                    $language
                );
            }

            return (string) trans(
                'firefly.recurring_monthly',
                ['dayOfMonth' => $repetition->repetition_moment, 'skip' => $repetition->repetition_skip - 1],
                $language
            );
        }
        if ('ndom' === $repetition->repetition_type) {
            $parts     = explode(',', $repetition->repetition_moment);
            // first part is number of week, second is weekday.
            $dayOfWeek = trans(sprintf('config.dow_%s', $parts[1]), [], $language);

            return (string) trans('firefly.recurring_ndom', ['weekday' => $dayOfWeek, 'dayOfMonth' => $parts[0]], $language);
        }
        if ('yearly' === $repetition->repetition_type) {
            $today       = today(config('app.timezone'))->endOfYear();
            $repDate     = Carbon::createFromFormat('Y-m-d', $repetition->repetition_moment);
            if (null === $repDate) {
                $repDate = clone $today;
            }
            $diffInYears = (int) $today->diffInYears($repDate, true);
            $repDate->addYears($diffInYears); // technically not necessary.
            $string      = $repDate->isoFormat((string) trans('config.month_and_day_no_year_js'));

            return (string) trans('firefly.recurring_yearly', ['date' => $string], $language);
        }

        return '';
    }

    public function searchRecurrence(string $query, int $limit): Collection
    {
        $search = $this->user->recurrences();
        if ('' !== $query) {
            $search->whereLike('recurrences.title', sprintf('%%%s%%', $query));
        }
        $search
            ->orderBy('recurrences.title', 'ASC')
        ;

        return $search->take($limit)->get(['id', 'title', 'description']);
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): Recurrence
    {
        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    public function totalTransactions(Recurrence $recurrence, RecurrenceRepetition $repetition): int
    {
        // if repeat = null just return 0.
        if (null === $recurrence->repeat_until && 0 === (int) $recurrence->repetitions) {
            return 0;
        }
        // expect X transactions then stop. Return that number
        if (null === $recurrence->repeat_until && 0 !== (int) $recurrence->repetitions) {
            return (int) $recurrence->repetitions;
        }

        // need to calculate, this depends on the repetition:
        if (null !== $recurrence->repeat_until && 0 === (int) $recurrence->repetitions) {
            $occurrences = $this->getOccurrencesInRange($repetition, $recurrence->first_date ?? today(), $recurrence->repeat_until);

            return count($occurrences);
        }

        return 0;
    }

    /**
     * Generate events in the date range.
     */
    public function getOccurrencesInRange(RecurrenceRepetition $repetition, Carbon $start, Carbon $end): array
    {
        $occurrences = [];
        $mutator     = clone $start;
        $mutator->startOfDay();
        $skipMod     = $repetition->repetition_skip + 1;
        app('log')->debug(sprintf('Calculating occurrences for rep type "%s"', $repetition->repetition_type));
        app('log')->debug(sprintf('Mutator is now: %s', $mutator->format('Y-m-d')));

        if ('daily' === $repetition->repetition_type) {
            $occurrences = $this->getDailyInRange($mutator, $end, $skipMod);
        }
        if ('weekly' === $repetition->repetition_type) {
            $occurrences = $this->getWeeklyInRange($mutator, $end, $skipMod, $repetition->repetition_moment);
        }
        if ('monthly' === $repetition->repetition_type) {
            $occurrences = $this->getMonthlyInRange($mutator, $end, $skipMod, $repetition->repetition_moment);
        }
        if ('ndom' === $repetition->repetition_type) {
            $occurrences = $this->getNdomInRange($mutator, $end, $skipMod, $repetition->repetition_moment);
        }
        if ('yearly' === $repetition->repetition_type) {
            $occurrences = $this->getYearlyInRange($mutator, $end, $skipMod, $repetition->repetition_moment);
        }

        // filter out all the weekend days:
        return $this->filterWeekends($repetition, $occurrences);
    }

    /**
     * Update a recurring transaction.
     *
     * @throws FireflyException
     */
    public function update(Recurrence $recurrence, array $data): Recurrence
    {
        /** @var RecurrenceUpdateService $service */
        $service = app(RecurrenceUpdateService::class);

        return $service->update($recurrence, $data);
    }
}
