<?php

namespace FireflyIII\Database\TransactionJournal;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface TransactionJournalInterface
 *
 * @package FireflyIII\Database
 */
interface TransactionJournalInterface
{
    /**
     * Get the very first transaction journal.
     *
     * @return mixed
     */
    public function first();

    /**
     * @param Carbon $date
     *
     * @return float
     */
    public function getSumOfExpensesByMonth(Carbon $date);

    /**
     * @param Carbon $date
     *
     * @return float
     */
    public function getSumOfIncomesByMonth(Carbon $date);

} 
