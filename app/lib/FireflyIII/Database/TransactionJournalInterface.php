<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 24/10/14
 * Time: 07:17
 */

namespace FireflyIII\Database;
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