<?php

namespace Firefly\Storage\TransactionJournal;

use Carbon\Carbon;

/**
 * Interface TransactionJournalRepositoryInterface
 *
 * @package Firefly\Storage\TransactionJournal
 */
interface TransactionJournalRepositoryInterface
{
    /**
     * @param \Account $from
     * @param \Account $toAccount
     * @param          $description
     * @param          $amount
     * @param Carbon   $date
     *
     * @return mixed
     */
    public function createSimpleJournal(\Account $from, \Account $toAccount, $description, $amount, Carbon $date);

    /**
     * @return mixed
     */
    public function get();


    /**
     * @param \User $user
     *
     * @return mixed
     */
    public function overruleUser(\User $user);

    /**
     * @param $what
     * @param $data
     *
     * @return mixed
     */
    public function store($what, $data);

    /**
     * @param \TransactionJournal $journal
     * @param                     $data
     *
     * @return mixed
     */
    public function update(\TransactionJournal $journal, $data);

    /**
     * @param $type
     *
     * @return \TransactionType
     */
    public function getTransactionType($type);

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

    /**
     * @param \Account $account
     * @param Carbon   $date
     *
     * @return mixed
     */
    public function getByAccountAndDate(\Account $account, Carbon $date);

    /**
     * @param \TransactionType $type
     * @param int              $count
     * @param Carbon           $start
     * @param Carbon           $end
     *
     * @return mixed
     */
    public function paginate(\TransactionType $type, $count = 25, Carbon $start = null, Carbon $end = null);

}