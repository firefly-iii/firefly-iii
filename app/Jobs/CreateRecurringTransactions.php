<?php

namespace FireflyIII\Jobs;

use Carbon\Carbon;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Log;

/**
 * Class CreateRecurringTransactions
 */
class CreateRecurringTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Carbon */
    private $date;
    /** @var JournalRepositoryInterface */
    private $journalRepository;
    /** @var RecurringRepositoryInterface */
    private $repository;

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
     * TODO check number of repetitions.
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function handle(): void
    {
        Log::debug('Now at start of CreateRecurringTransactions() job.');
        $recurrences = $this->repository->getAll();
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
            $this->repository->setUser($recurrence->user);
            $this->journalRepository->setUser($recurrence->user);
            Log::debug(sprintf('Now at recurrence #%d', $recurrence->id));
            $this->handleRepetitions($recurrence);
            Log::debug(sprintf('Done with recurrence #%c', $recurrence->id));
        }
        Log::debug('Done with handle()');
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
     * @param Recurrence $recurrence
     *
     * @return Carbon
     */
    private function getStartDate(Recurrence $recurrence): Carbon
    {
        $startDate = clone $recurrence->first_date;
        if (null !== $recurrence->latest_date && $recurrence->latest_date->gte($startDate)) {
            $startDate = clone $recurrence->latest_date;
            // jump to a day later.
            $startDate->addDay();

        }

        return $startDate;
    }

    /**
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
                'source_id'             => $transaction->source_account_id,
                'source_name'           => null,
                'destination_id'        => $transaction->destination_account_id,
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
     * @param Recurrence $recurrence
     * @param array      $occurrences
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function handleOccurrences(Recurrence $recurrence, array $occurrences): void
    {
        /** @var Carbon $date */
        foreach ($occurrences as $date) {
            Log::debug(sprintf('Now at date %s.', $date->format('Y-m-d')));
            if ($date->ne($this->date)) {
                Log::debug(sprintf('%s is not not today (%s)', $date->format('Y-m-d'), $this->date->format('Y-m-d')));

                continue;
            }
            Log::debug(sprintf('%s IS today (%s)', $date->format('Y-m-d'), $this->date->format('Y-m-d')));

            // create transaction array and send to factory.
            $array   = [
                'type'            => $recurrence->transactionType->type,
                'date'            => $date,
                'tags'            => $this->repository->getTags($recurrence),
                'user'            => $recurrence->user_id,
                'notes'           => trans('firefly.created_from_recurrence', ['id' => $recurrence->id, 'title' => $recurrence->title]),

                // journal data:
                'description'     => $recurrence->recurrenceTransactions()->first()->description,
                'piggy_bank_id'   => null,
                'piggy_bank_name' => null,
                'bill_id'         => null,
                'bill_name'       => null,
                'recurrence_id'   => $recurrence->id,

                // transaction data:
                'transactions'    => $this->getTransactionData($recurrence),
            ];
            $journal = $this->journalRepository->store($array);
            Log::info(sprintf('Created new journal #%d', $journal->id));
            // update recurring thing:
            $recurrence->latest_date = $date;
            $recurrence->save();
        }
    }

    /**
     * Separate method that will loop all repetitions and do something with it:
     *
     * @param Recurrence $recurrence
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function handleRepetitions(Recurrence $recurrence): void
    {
        /** @var RecurrenceRepetition $repetition */
        foreach ($recurrence->recurrenceRepetitions as $repetition) {
            Log::debug(
                sprintf(
                    'Now repeating %s with value "%s", skips every %d time(s)', $repetition->repetition_type, $repetition->repetition_moment,
                    $repetition->repetition_skip
                )
            );

            // start looping from $startDate to today perhaps we have a hit?
            $occurrences = $this->repository->getOccurrencesInRange($repetition, $recurrence->first_date, $this->date);
            Log::debug(
                sprintf(
                    'Calculated %d occurrences between %s and %s', \count($occurrences), $recurrence->first_date->format('Y-m-d'), $this->date->format('Y-m-d')
                ), $this->debugArray($occurrences)
            );

            $this->handleOccurrences($recurrence, $occurrences);
        }
    }

    /**
     * @param Recurrence $recurrence
     *
     * @return bool
     */
    private function hasFiredToday(Recurrence $recurrence): bool
    {
        return null !== $recurrence->latest_date && $recurrence->latest_date->eq($this->date);
    }

    /**
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
     * @param Recurrence $recurrence
     *
     * @return bool
     */
    private function validRecurrence(Recurrence $recurrence): bool
    {
        // is not active.
        if (!$this->active($recurrence)) {
            Log::info(sprintf('Recurrence #%d is not active. Skipped.', $recurrence->id));

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
