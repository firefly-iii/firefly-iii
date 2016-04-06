<?php
declare(strict_types = 1);

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Interface ReportQueryInterface
 *
 * @package FireflyIII\Helpers\Report
 */
interface ReportQueryInterface
{

    /**
     * Returns an array of the amount of money spent in the given accounts (on withdrawals, opening balances and transfers)
     * grouped by month like so: "2015-01" => '123.45'
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function earnedPerMonth(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * This method returns all the "out" transaction journals for the given account and given period. The amount
     * is stored in "journalAmount".
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function expense(Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * This method returns all the "in" transaction journals for the given account and given period. The amount
     * is stored in "journalAmount".
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function income(Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * Returns an array of the amount of money spent in the given accounts (on withdrawals, opening balances and transfers)
     * grouped by month like so: "2015-01" => '123.45'
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentPerMonth(Collection $accounts, Carbon $start, Carbon $end): array;


}
