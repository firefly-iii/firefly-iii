<?php
declare(strict_types=1);


/**
 * CreateRecurringTransactions.php
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

namespace FireflyIII\Jobs;

use Carbon\Carbon;
use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\Rule;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CreateRecurringTransactions.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateRecurringTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Carbon The current date */
    private $date;
    /** @var JournalRepositoryInterface Journal repository */
    private $journalRepository;
    /** @var RecurringRepositoryInterface Recurring transactions repository. */
    private $repository;
    /** @var array The users rules. */
    private $rules = [];

    /**
     * Create a new job instance.
     *
     * @param Carbon $date
     */
    public function __construct(Carbon $date)
    {
        $date->startOfDay();
        $this->date              = $date;
        $this->repository        = app(RecurringRepositoryInterface::class);
        $this->journalRepository = app(JournalRepositoryInterface::class);

    }

    /**
     * Execute the job.
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function handle(): void
    {
        Log::debug(sprintf('Now at start of CreateRecurringTransactions() job for %s.', $this->date->format('D d M Y')));
        $recurrences = $this->repository->getAll();
        $result      = [];
        Log::debug(sprintf('Count of collection is %d', $recurrences->count()));

        /** @var Collection $filtered */
        $filtered = $recurrences->filter(
            function (Recurrence $recurrence) {
                return $this->validRecurrence($recurrence);

            }
        );
        Log::debug(sprintf('Left after filtering is %d', $filtered->count()));
        /** @var Recurrence $recurrence */
        foreach ($filtered as $recurrence) {
            if (!isset($result[$recurrence->user_id])) {
                $result[$recurrence->user_id] = new Collection;
            }
            $this->repository->setUser($recurrence->user);
            $this->journalRepository->setUser($recurrence->user);
            Log::debug(sprintf('Now at recurrence #%d', $recurrence->id));
            $created = $this->handleRepetitions($recurrence);
            Log::debug(sprintf('Done with recurrence #%d', $recurrence->id));
            $result[$recurrence->user_id] = $result[$recurrence->user_id]->merge($created);

            // apply rules:
            $this->applyRules($recurrence->user, $created);
        }

        Log::debug('Now running report thing.');
        // will now send email to users.
        foreach ($result as $userId => $journals) {
            event(new RequestedReportOnJournals($userId, $journals));
        }

        Log::debug('Done with handle()');

        // clear cache:
        app('preferences')->mark();
    }

    /**
     * Return recurring transaction is active.
     *
     * @param Recurrence $recurrence
     *
     * @return bool
     */
    private function active(Recurrence $recurrence): bool
    {
        return $recurrence->active;
    }

    /**
     * Apply the users rules to newly created journals.
     *
     * @param User       $user
     * @param Collection $journals
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function applyRules(User $user, Collection $journals): void
    {
        $userId = $user->id;
        if (!isset($this->rules[$userId])) {
            $this->rules[$userId] = $this->getRules($user);
        }
        // run the rules:
        if ($this->rules[$userId]->count() > 0) {
            /** @var TransactionJournal $journal */
            foreach ($journals as $journal) {
                $this->rules[$userId]->each(
                    function (Rule $rule) use ($journal) {
                        Log::debug(sprintf('Going to apply rule #%d to journal %d.', $rule->id, $journal->id));
                        /** @var Processor $processor */
                        $processor = app(Processor::class);
                        $processor->make($rule);
                        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                        $processor->handleTransactionJournal($journal);
                        if ($rule->stop_processing) {
                            return;
                        }
                    }
                );
            }
        }
    }

    /**
     * Helper function for debug information.
     *
     * @param array $occurrences
     *
     * @return array
     */
    private function debugArray(array $occurrences): array
    {
        $return = [];
        foreach ($occurrences as $entry) {
            $return[] = $entry->format('Y-m-d');
        }

        return $return;
    }

    /**
     * Get the users rules.
     *
     * @param User $user
     *
     * @return Collection
     */
    private function getRules(User $user): Collection
    {
        /** @var RuleRepositoryInterface $repository */
        $repository = app(RuleRepositoryInterface::class);
        $repository->setUser($user);
        $set = $repository->getForImport();

        Log::debug(sprintf('Found %d user rules.', $set->count()));

        return $set;
    }

    /**
     * Get the start date of a recurrence.
     *
     * @param Recurrence $recurrence
     *
     * @return Carbon
     */
    private function getStartDate(Recurrence $recurrence): Carbon
    {
        $startDate = clone $recurrence->first_date;
        if (null !== $recurrence->latest_date && $recurrence->latest_date->gte($startDate)) {
            $startDate = clone $recurrence->latest_date;
        }

        return $startDate;
    }

    /**
     * Get transaction information from a recurring transaction.
     *
     * @param Recurrence $recurrence
     *
     * @return array
     */
    private function getTransactionData(Recurrence $recurrence): array
    {
        $transactions = $recurrence->recurrenceTransactions()->get();
        $return       = [];
        /** @var RecurrenceTransaction $transaction */
        foreach ($transactions as $index => $transaction) {
            $single   = [
                'currency_id'           => $transaction->transaction_currency_id,
                'currency_code'         => null,
                'description'           => null,
                'amount'                => $transaction->amount,
                'budget_id'             => $this->repository->getBudget($transaction),
                'budget_name'           => null,
                'category_id'           => null,
                'category_name'         => $this->repository->getCategory($transaction),
                'source_id'             => $transaction->source_id,
                'source_name'           => null,
                'destination_id'        => $transaction->destination_id,
                'destination_name'      => null,
                'foreign_currency_id'   => $transaction->foreign_currency_id,
                'foreign_currency_code' => null,
                'foreign_amount'        => $transaction->foreign_amount,
                'reconciled'            => false,
                'identifier'            => $index,
            ];
            $return[] = $single;
        }

        return $return;
    }

    /**
     * Check if the occurences should be executed.
     *
     * @param Recurrence $recurrence
     * @param array      $occurrences
     *
     * @return Collection
     * @throws \FireflyIII\Exceptions\FireflyException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function handleOccurrences(Recurrence $recurrence, array $occurrences): Collection
    {
        $collection = new Collection;
        /** @var Carbon $date */
        foreach ($occurrences as $date) {
            Log::debug(sprintf('Now at date %s.', $date->format('Y-m-d')));
            if ($date->ne($this->date)) {
                Log::debug(sprintf('%s is not not today (%s)', $date->format('Y-m-d'), $this->date->format('Y-m-d')));

                continue;
            }
            Log::debug(sprintf('%s IS today (%s)', $date->format('Y-m-d'), $this->date->format('Y-m-d')));

            // count created journals on THIS day.
            $journalCount = $this->repository->getJournalCount($recurrence, $date, $date);
            if ($journalCount > 0) {
                Log::info(sprintf('Already created %d journal(s) for date %s', $journalCount, $date->format('Y-m-d')));
                continue;
            }

            // create transaction array and send to factory.
            $array   = [
                'type'            => $recurrence->transactionType->type,
                'date'            => $date,
                'tags'            => $this->repository->getTags($recurrence),
                'user'            => $recurrence->user_id,
                'notes'           => (string)trans('firefly.created_from_recurrence', ['id' => $recurrence->id, 'title' => $recurrence->title]),
                // journal data:
                'description'     => $recurrence->recurrenceTransactions()->first()->description,
                'piggy_bank_id'   => null,
                'piggy_bank_name' => null,
                'bill_id'         => null,
                'bill_name'       => null,
                'recurrence_id'   => (int)$recurrence->id,
                // transaction data:
                'transactions'    => $this->getTransactionData($recurrence),
            ];
            $journal = $this->journalRepository->store($array);
            Log::info(sprintf('Created new journal #%d', $journal->id));

            $collection->push($journal);
            // update recurring thing:
            $recurrence->latest_date = $date;
            $recurrence->save();
        }

        return $collection;
    }

    /**
     * Separate method that will loop all repetitions and do something with it. Will return
     * all created transaction journals.
     *
     * @param Recurrence $recurrence
     *
     * @return Collection
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function handleRepetitions(Recurrence $recurrence): Collection
    {
        $collection = new Collection;
        /** @var RecurrenceRepetition $repetition */
        foreach ($recurrence->recurrenceRepetitions as $repetition) {
            Log::debug(
                sprintf(
                    'Now repeating %s with value "%s", skips every %d time(s)', $repetition->repetition_type, $repetition->repetition_moment,
                    $repetition->repetition_skip
                )
            );

            // start looping from $startDate to today perhaps we have a hit?
            // add two days to $this->date so we always include the weekend.
            $includeWeekend = clone $this->date;
            $includeWeekend->addDays(2);
            $occurrences = $this->repository->getOccurrencesInRange($repetition, $recurrence->first_date, $includeWeekend);
            Log::debug(
                sprintf(
                    'Calculated %d occurrences between %s and %s',
                    \count($occurrences),
                    $recurrence->first_date->format('Y-m-d'),
                    $includeWeekend->format('Y-m-d')
                ), $this->debugArray($occurrences)
            );
            unset($includeWeekend);

            $result     = $this->handleOccurrences($recurrence, $occurrences);
            $collection = $collection->merge($result);
        }

        return $collection;
    }

    /**
     * Has the recurrence fired today.
     *
     * @param Recurrence $recurrence
     *
     * @return bool
     */
    private function hasFiredToday(Recurrence $recurrence): bool
    {
        return null !== $recurrence->latest_date && $recurrence->latest_date->eq($this->date);
    }

    /**
     * Has the reuccrence started yet.
     *
     * @param $recurrence
     *
     * @return bool
     */
    private function hasNotStartedYet(Recurrence $recurrence): bool
    {
        $startDate = $this->getStartDate($recurrence);

        return $startDate->gt($this->date);
    }

    /**
     * Return true if the $repeat_until date is in the past.
     *
     * @param Recurrence $recurrence
     *
     * @return bool
     */
    private function repeatUntilHasPassed(Recurrence $recurrence): bool
    {
        // date has passed
        return null !== $recurrence->repeat_until && $recurrence->repeat_until->lt($this->date);
    }

    /**
     * Is the info in the recurrence valid?
     *
     * @param Recurrence $recurrence
     *
     * @return bool
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validRecurrence(Recurrence $recurrence): bool
    {
        // is not active.
        if (!$this->active($recurrence)) {
            Log::info(sprintf('Recurrence #%d is not active. Skipped.', $recurrence->id));

            return false;
        }

        // has repeated X times.
        $journalCount = $this->repository->getJournalCount($recurrence, null, null);
        if (0 !== $recurrence->repetitions && $journalCount >= $recurrence->repetitions) {
            Log::info(sprintf('Recurrence #%d has run %d times, so will run no longer.', $recurrence->id, $recurrence->repetitions));

            return false;
        }

        // is no longer running
        if ($this->repeatUntilHasPassed($recurrence)) {
            Log::info(
                sprintf(
                    'Recurrence #%d was set to run until %s, and today\'s date is %s. Skipped.',
                    $recurrence->id,
                    $recurrence->repeat_until->format('Y-m-d'),
                    $this->date->format('Y-m-d')
                )
            );

            return false;
        }

        // first_date is in the future
        if ($this->hasNotStartedYet($recurrence)) {
            Log::info(
                sprintf(
                    'Recurrence #%d is set to run on %s, and today\'s date is %s. Skipped.',
                    $recurrence->id,
                    $recurrence->first_date->format('Y-m-d'),
                    $this->date->format('Y-m-d')
                )
            );

            return false;
        }

        // already fired today (with success):
        if ($this->hasFiredToday($recurrence)) {
            Log::info(sprintf('Recurrence #%d has already fired today. Skipped.', $recurrence->id));

            return false;
        }

        return true;

    }
}
