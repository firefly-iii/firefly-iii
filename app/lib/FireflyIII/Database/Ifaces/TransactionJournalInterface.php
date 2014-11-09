<?php

namespace FireflyIII\Database\Ifaces;
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
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getInDateRange(Carbon $start, Carbon $end);

    /**
     * Get the very first transaction journal.
     * @return mixed
     */
    public function first();

    /**
     * @param Carbon $date
     *
     * @return float
     */
    public function getSumOfIncomesByMonth(Carbon $date);

    /**
     * @param Carbon $date
     *
     * @return float
     */
    public function getSumOfExpensesByMonth(Carbon $date);

} 