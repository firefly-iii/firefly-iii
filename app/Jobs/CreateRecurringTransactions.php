<?php

/**
 * CreateRecurringTransactions.php
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

namespace FireflyIII\Jobs;

use Carbon\Carbon;
use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Exceptions\DuplicateTransactionException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Class CreateRecurringTransactions.
 */
class CreateRecurringTransactions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int                                  $created;
    public int                                  $executed;
    public int                                  $submitted;
    private Carbon                              $date;
    private bool                                $force;
    private TransactionGroupRepositoryInterface $groupRepository;
    private Collection                          $groups;
    private JournalRepositoryInterface          $journalRepository;
    private Collection                          $recurrences;
    private RecurringRepositoryInterface        $repository;

    /**
     * Create a new job instance.
     */
    public function __construct(?Carbon $date)
    {
        $newDate                 = new Carbon();
        $newDate->startOfDay();
        $this->date              = $newDate;

        if (null !== $date) {
            $newDate    = clone $date;
            $newDate->startOfDay();
            $this->date = $newDate;
        }
        $this->repository        = app(RecurringRepositoryInterface::class);
        $this->journalRepository = app(JournalRepositoryInterface::class);
        $this->groupRepository   = app(TransactionGroupRepositoryInterface::class);
        $this->force             = false;
        $this->submitted         = 0;
        $this->executed          = 0;
        $this->created           = 0;
        $this->recurrences       = new Collection();
        $this->groups            = new Collection();

        app('log')->debug(sprintf('Created new CreateRecurringTransactions("%s")', $this->date->format('Y-m-d')));
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app('log')->debug(sprintf('Now at start of CreateRecurringTransactions() job for %s.', $this->date->format('D d M Y')));

        // only use recurrences from database if there is no collection submitted.
        if (0 !== count($this->recurrences)) {
            app('log')->debug('Using predetermined set of recurrences.');
        }
        if (0 === count($this->recurrences)) {
            app('log')->debug('Grab all recurrences from the database.');
            $this->recurrences = $this->repository->getAll();
        }

        $result          = [];
        $count           = $this->recurrences->count();
        $this->submitted = $count;
        app('log')->debug(sprintf('Count of collection is %d', $count));

        // filter recurrences:
        $filtered        = $this->filterRecurrences($this->recurrences);
        app('log')->debug(sprintf('Left after filtering is %d', $filtered->count()));

        /** @var Recurrence $recurrence */
        foreach ($filtered as $recurrence) {
            if (!array_key_exists($recurrence->user_id, $result)) {
                $result[$recurrence->user_id] = new Collection();
            }
            $this->repository->setUser($recurrence->user);
            $this->journalRepository->setUser($recurrence->user);
            $this->groupRepository->setUser($recurrence->user);

            // clear cache for user
            app('preferences')->setForUser($recurrence->user, 'lastActivity', microtime());

            app('log')->debug(sprintf('Now at recurrence #%d of user #%d', $recurrence->id, $recurrence->user_id));
            $createdReps                  = $this->handleRepetitions($recurrence);
            app('log')->debug(sprintf('Done with recurrence #%d', $recurrence->id));
            $result[$recurrence->user_id] = $result[$recurrence->user_id]->merge($createdReps);
            ++$this->executed;
        }

        app('log')->debug('Now running report thing.');
        // will now send email to users.
        foreach ($result as $userId => $journals) {
            event(new RequestedReportOnJournals($userId, $journals));
        }

        app('log')->debug('Done with handle()');

        // clear cache:
        app('preferences')->mark();
    }

    private function filterRecurrences(Collection $recurrences): Collection
    {
        return $recurrences->filter(
            function (Recurrence $recurrence) {
                return $this->validRecurrence($recurrence);
            }
        );
    }

    /**
     * Is the info in the recurrence valid?
     */
    private function validRecurrence(Recurrence $recurrence): bool
    {
        app('log')->debug(sprintf('Now filtering recurrence #%d, owned by user #%d', $recurrence->id, $recurrence->user_id));
        // is not active.
        if (!$this->active($recurrence)) {
            app('log')->info(sprintf('Recurrence #%d is not active. Skipped.', $recurrence->id));

            return false;
        }

        // has repeated X times.
        $journalCount = $this->repository->getJournalCount($recurrence);
        if (0 !== $recurrence->repetitions && $journalCount >= $recurrence->repetitions && false === $this->force) {
            app('log')->info(sprintf('Recurrence #%d has run %d times, so will run no longer.', $recurrence->id, $recurrence->repetitions));

            return false;
        }

        // is no longer running
        if ($this->repeatUntilHasPassed($recurrence)) {
            app('log')->info(
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
            app('log')->info(
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
        if (false === $this->force && $this->hasFiredToday($recurrence)) {
            app('log')->info(sprintf('Recurrence #%d has already fired today. Skipped.', $recurrence->id));

            return false;
        }
        app('log')->debug('Will be included.');

        return true;
    }

    /**
     * Return recurring transaction is active.
     */
    private function active(Recurrence $recurrence): bool
    {
        return $recurrence->active;
    }

    /**
     * Return true if the $repeat_until date is in the past.
     */
    private function repeatUntilHasPassed(Recurrence $recurrence): bool
    {
        // date has passed
        return null !== $recurrence->repeat_until && $recurrence->repeat_until->lt($this->date);
    }

    /**
     * Has the recurrence started yet?
     */
    private function hasNotStartedYet(Recurrence $recurrence): bool
    {
        $startDate = $this->getStartDate($recurrence);
        app('log')->debug(sprintf('Start date is %s', $startDate->format('Y-m-d')));

        return $startDate->gt($this->date);
    }

    /**
     * Get the start date of a recurrence.
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
     * Has the recurrence fired today.
     */
    private function hasFiredToday(Recurrence $recurrence): bool
    {
        return null !== $recurrence->latest_date && $recurrence->latest_date->eq($this->date);
    }

    /**
     * Separate method that will loop all repetitions and do something with it. Will return
     * all created transaction journals.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    private function handleRepetitions(Recurrence $recurrence): Collection
    {
        $collection = new Collection();

        /** @var RecurrenceRepetition $repetition */
        foreach ($recurrence->recurrenceRepetitions as $repetition) {
            app('log')->debug(
                sprintf(
                    'Now repeating %s with value "%s", skips every %d time(s)',
                    $repetition->repetition_type,
                    $repetition->repetition_moment,
                    $repetition->repetition_skip
                )
            );

            // start looping from $startDate to today perhaps we have a hit?
            // add two days to $this->date, so we always include the weekend.
            $includeWeekend = clone $this->date;
            $includeWeekend->addDays(2);
            $occurrences    = $this->repository->getOccurrencesInRange($repetition, $recurrence->first_date, $includeWeekend);

            unset($includeWeekend);

            $result         = $this->handleOccurrences($recurrence, $repetition, $occurrences);
            $collection     = $collection->merge($result);
        }

        return $collection;
    }

    /**
     * Check if the occurrences should be executed.
     *
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    private function handleOccurrences(Recurrence $recurrence, RecurrenceRepetition $repetition, array $occurrences): Collection
    {
        $collection = new Collection();

        /** @var Carbon $date */
        foreach ($occurrences as $date) {
            $result = $this->handleOccurrence($recurrence, $repetition, $date);
            if (null !== $result) {
                $collection->push($result);
            }
        }

        return $collection;
    }

    /**
     * @throws DuplicateTransactionException
     * @throws FireflyException
     */
    private function handleOccurrence(Recurrence $recurrence, RecurrenceRepetition $repetition, Carbon $date): ?TransactionGroup
    {
        $date->startOfDay();
        if ($date->ne($this->date)) {
            return null;
        }
        app('log')->debug(sprintf('%s IS today (%s)', $date->format('Y-m-d'), $this->date->format('Y-m-d')));

        // count created journals on THIS day.
        $journalCount            = $this->repository->getJournalCount($recurrence, $date, $date);
        if ($journalCount > 0 && false === $this->force) {
            app('log')->info(sprintf('Already created %d journal(s) for date %s', $journalCount, $date->format('Y-m-d')));

            return null;
        }

        if ($this->repository->createdPreviously($recurrence, $date) && false === $this->force) {
            app('log')->info('There is a transaction already made for this date, so will not be created now');

            return null;
        }

        if ($journalCount > 0 && true === $this->force) {
            app('log')->warning(sprintf('Already created %d groups for date %s but FORCED to continue.', $journalCount, $date->format('Y-m-d')));
        }

        // create transaction array and send to factory.
        $groupTitle              = null;
        $count                   = $recurrence->recurrenceTransactions->count();
        // #8844, if there is one recurrence transaction, use the first title as the title.
        // #9305, if there is one recurrence transaction, group title must be NULL.
        $groupTitle = null;

        // #8844, if there are more, use the recurrence transaction itself.
        if ($count > 1) {
            $groupTitle = $recurrence->title;
        }

        if (0 === $count) {
            app('log')->error('No transactions to be created in this recurrence. Cannot continue.');

            return null;
        }

        $array                   = [
            'user'         => $recurrence->user_id,
            'group_title'  => $groupTitle,
            'transactions' => $this->getTransactionData($recurrence, $repetition, $date),
        ];

        /** @var TransactionGroup $group */
        $group                   = $this->groupRepository->store($array);
        ++$this->created;
        app('log')->info(sprintf('Created new transaction group #%d', $group->id));

        // trigger event:
        event(new StoredTransactionGroup($group, $recurrence->apply_rules, true));
        $this->groups->push($group);

        // update recurring thing:
        $recurrence->latest_date = $date;
        $recurrence->save();

        return $group;
    }

    /**
     * Get transaction information from a recurring transaction.
     */
    private function getTransactionData(Recurrence $recurrence, RecurrenceRepetition $repetition, Carbon $date): array
    {
        // total transactions expected for this recurrence:
        $total        = $this->repository->totalTransactions($recurrence, $repetition);
        $count        = $this->repository->getJournalCount($recurrence) + 1;
        $transactions = $recurrence->recurrenceTransactions()->get();

        /** @var RecurrenceTransaction $first */
        $first        = $transactions->first();
        $return       = [];

        /** @var RecurrenceTransaction $transaction */
        foreach ($transactions as $index => $transaction) {
            $single   = [
                'type'                  => null === $transaction?->transactionType?->type ? strtolower($recurrence->transactionType->type) : strtolower($transaction->transactionType->type),
                'date'                  => $date,
                'user'                  => $recurrence->user_id,
                'currency_id'           => $transaction->transaction_currency_id,
                'currency_code'         => null,
                'description'           => $transaction->description,
                'amount'                => $transaction->amount,
                'budget_id'             => $this->repository->getBudget($transaction),
                'budget_name'           => null,
                'category_id'           => $this->repository->getCategoryId($transaction),
                'category_name'         => $this->repository->getCategoryName($transaction),
                'source_id'             => $transaction->source_id,
                'source_name'           => null,
                'destination_id'        => $transaction->destination_id,
                'destination_name'      => null,
                'foreign_currency_id'   => $transaction->foreign_currency_id,
                'foreign_currency_code' => null,
                'foreign_amount'        => $transaction->foreign_amount,
                'reconciled'            => false,
                'identifier'            => $index,
                'recurrence_id'         => $recurrence->id,
                'order'                 => $index,
                'notes'                 => (string)trans('firefly.created_from_recurrence', ['id' => $recurrence->id, 'title' => $recurrence->title]),
                'tags'                  => $this->repository->getTags($transaction),
                'piggy_bank_id'         => $this->repository->getPiggyBank($transaction),
                'piggy_bank_name'       => null,
                'bill_id'               => $this->repository->getBillId($transaction),
                'bill_name'             => null,
                'recurrence_total'      => $total,
                'recurrence_count'      => $count,
                'recurrence_date'       => $date,
            ];
            $return[] = $single;
        }

        return $return;
    }

    public function setDate(Carbon $date): void
    {
        $newDate    = clone $date;
        $newDate->startOfDay();
        $this->date = $newDate;
    }

    public function setForce(bool $force): void
    {
        $this->force = $force;
    }

    public function setRecurrences(Collection $recurrences): void
    {
        $this->recurrences = $recurrences;
    }
}
