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

} 