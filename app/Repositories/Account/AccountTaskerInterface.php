<?php
/**
 * AccountTaskerInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\Account as AccountCollection;
use Illuminate\Support\Collection;

/**
 * Interface AccountTaskerInterface
 *
 * @package FireflyIII\Repositories\Account
 */
interface AccountTaskerInterface
{

    /**
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountTasker::amountInPeriod()
     *
     * @return string
     */
    public function amountInInPeriod(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountTasker::amountInPeriod()
     *
     * @return string
     */
    public function amountOutInPeriod(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountTasker::financialReport()
     *
     * @return Collection
     *
     */
    public function expenseReport(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): Collection;

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return AccountCollection
     */
    public function getAccountReport(Carbon $start, Carbon $end, Collection $accounts): AccountCollection;

    /**
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountTasker::financialReport()
     *
     * @return Collection
     *
     */
    public function incomeReport(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): Collection;

}
