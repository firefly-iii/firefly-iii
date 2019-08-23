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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
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
use FireflyIII\Support\Repositories\Recurring\FiltersWeekends;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Class RecurringRepository
 */
class RecurringRepository implements RecurringRepositoryInterface
{
    use CalculateRangeOccurrences, CalculateXOccurrences, FiltersWeekends;
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

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
     * @param RecurrenceTransaction $recTransaction
     *
     * @return null|int
     */
    public function getBudget(RecurrenceTransaction $recTransaction): ?int
    {
        $return = 0;
        /** @var RecurrenceTransactionMeta $meta */
        foreach ($recTransaction->recurrenceTransactionMeta as $meta) {
            if ('budget_id' === $meta->name) {
                $return = (int)$meta->value;
            }
        }

        return 0 === $return ? null : $return;
    }

    /**
     * Get the category from a recurring transaction transaction.
     *
     * @param RecurrenceTransaction $recTransaction
     *
     * @return null|string
     */
    public function getCategory(RecurrenceTransaction $recTransaction): ?string
    {
        $return = '';
        /** @var RecurrenceTransactionMeta $meta */
        foreach ($recTransaction->recurrenceTransactionMeta as $meta) {
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
     * @return int
     */
    public function getJournalCount(Recurrence $recurrence, Carbon $start = null, Carbon $end = null): int
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

        return $query->get(['transaction_journals.*'])->count();
    }

    /**
     * Get journal ID's for journals created by this recurring transaction.
     *
     * @param Recurrence $recurrence
     *
     * @return array
     */
    public function getJournalIds(Recurrence $recurrence): array
    {
        return TransactionJournalMeta::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
                                     ->where('transaction_journals.user_id', $this->user->id)
                                     ->where('journal_meta.name', '=', 'recurrence_id')
                                     ->where('journal_meta.data', '=', json_encode((string)$recurrence->id))
                                     ->get(['journal_meta.transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
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
     * @return array
     *
     */
    public function getOccurrencesInRange(RecurrenceRepetition $repetition, Carbon $start, Carbon $end): array
    {
        $occurrences = [];
        $mutator     = clone $start;
        $mutator->startOfDay();
        $skipMod = $repetition->repetition_skip + 1;
        Log::debug(sprintf('Calculating occurrences for rep type "%s"', $repetition->repetition_type));
        Log::debug(sprintf('Mutator is now: %s', $mutator->format('Y-m-d')));

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
        $occurrences = $this->filterWeekends($repetition, $occurrences);

        return $occurrences;
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return PiggyBank|null
     */
    public function getPiggyBank(Recurrence $recurrence): ?PiggyBank
    {
        $meta = $recurrence->recurrenceMeta;
        /** @var RecurrenceMeta $metaEntry */
        foreach ($meta as $metaEntry) {
            if ('piggy_bank_id' === $metaEntry->name) {
                $piggyId = (int)$metaEntry->value;

                return $this->user->piggyBanks()->where('piggy_banks.id', $piggyId)->first(['piggy_banks.*']);
            }
        }

        return null;
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
            $search[] = (int)$journalId;
        }
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($recurrence->user);
        $collector->withCategoryInformation()->withBudgetInformation()->setLimit($pageSize)->setPage($page)
                  ->withAccountInformation();
        $collector->setJournalIds($search);

        return $collector->getPaginatedGroups();
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
            $search[] = (int)$journalId;
        }
        if (0 === count($search)) {

            return new Collection;
        }

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($recurrence->user);
        $collector->withCategoryInformation()->withBudgetInformation()->withAccountInformation();
        // filter on specific journals.
        $collector->setJournalIds($search);

        return $collector->getGroups();
    }

    /**
     * Calculate the next X iterations starting on the date given in $date.
     *
     * @param RecurrenceRepetition $repetition
     * @param Carbon               $date
     * @param int                  $count
     *
     * @return array
     *
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
        $occurrences = $this->filterWeekends($repetition, $occurrences);

        return $occurrences;
    }

    /**
     * Parse the repetition in a string that is user readable.
     *
     * @param RecurrenceRepetition $repetition
     *
     * @return string
     *
     */
    public function repetitionDescription(RecurrenceRepetition $repetition): string
    {
        /** @var Preference $pref */
        $pref     = app('preferences')->getForUser($this->user, 'language', config('firefly.default_language', 'en_US'));
        $language = $pref->data;
        if ('daily' === $repetition->repetition_type) {
            return (string)trans('firefly.recurring_daily', [], $language);
        }
        if ('weekly' === $repetition->repetition_type) {
            $dayOfWeek = trans(sprintf('config.dow_%s', $repetition->repetition_moment), [], $language);

            return (string)trans('firefly.recurring_weekly', ['weekday' => $dayOfWeek], $language);
        }
        if ('monthly' === $repetition->repetition_type) {
            return (string)trans('firefly.recurring_monthly', ['dayOfMonth' => $repetition->repetition_moment], $language);
        }
        if ('ndom' === $repetition->repetition_type) {
            $parts = explode(',', $repetition->repetition_moment);
            // first part is number of week, second is weekday.
            $dayOfWeek = trans(sprintf('config.dow_%s', $parts[1]), [], $language);

            return (string)trans('firefly.recurring_ndom', ['weekday' => $dayOfWeek, 'dayOfMonth' => $parts[0]], $language);
        }
        if ('yearly' === $repetition->repetition_type) {
            //
            $today       = Carbon::now()->endOfYear();
            $repDate     = Carbon::createFromFormat('Y-m-d', $repetition->repetition_moment);
            $diffInYears = $today->diffInYears($repDate);
            $repDate->addYears($diffInYears); // technically not necessary.
            $string = $repDate->formatLocalized((string)trans('config.month_and_day_no_year'));

            return (string)trans('firefly.recurring_yearly', ['date' => $string], $language);
        }

        return '';

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
     * @throws FireflyException
     */
    public function store(array $data): Recurrence
    {
        /** @var RecurrenceFactory $factory */
        $factory = app(RecurrenceFactory::class);
        $factory->setUser($this->user);
        $result = $factory->create($data);
        if (null === $result) {
            throw new FireflyException($factory->getErrors()->first());
        }

        return $result;
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
}
