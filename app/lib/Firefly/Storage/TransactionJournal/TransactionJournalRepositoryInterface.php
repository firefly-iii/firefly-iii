<?php

namespace Firefly\Storage\TransactionJournal;

use Carbon\Carbon;
use Illuminate\Queue\Jobs\Job;

/**
 * Interface TransactionJournalRepositoryInterface
 *
 * @package Firefly\Storage\TransactionJournal
 */
interface TransactionJournalRepositoryInterface
{
    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importTransaction(Job $job, array $payload);

    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importTransfer(Job $job, array $payload);

    /**
     * @param \User $user
     *
     * @return mixed
     */
    public function overruleUser(\User $user);

    /**
     * Store a new transaction journal.
     *
     * @param $data
     *
     * @return \TransactionJournal|null
     */
    public function store(array $data);

    /**
     * @param \TransactionJournal $journal
     * @param \Account            $account
     * @param                     $amount
     *
     * @return mixed
     */
    public function saveTransaction(\TransactionJournal $journal, \Account $account, $amount);

    /**
     * @param \TransactionJournal $journal
     * @param                     $data
     *
     * @return \Transaction|null
     */
    public function update(\TransactionJournal $journal, $data);

    /**
     * @param $journalId
     *
     * @return mixed
     */
    public function find($journalId);

    /**
     * @param \Account $account
     * @param int      $count
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return mixed
     */
    public function getByAccountInDateRange(\Account $account, $count = 25, Carbon $start, Carbon $end);
}