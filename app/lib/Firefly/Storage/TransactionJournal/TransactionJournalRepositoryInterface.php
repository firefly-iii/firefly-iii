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
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return mixed
     */
    public function getByDateRange(Carbon $start, Carbon $end);

    /**
     * @param int $count
     *
     * @return mixed
     */
    public function paginate($count = 25);

}