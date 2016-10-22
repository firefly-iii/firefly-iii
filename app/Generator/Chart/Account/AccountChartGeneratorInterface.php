<?php
/**
 * AccountChartGeneratorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Chart\Account;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use Illuminate\Support\Collection;

/**
 * Interface AccountChartGeneratorInterface
 *
 * @package FireflyIII\Generator\Chart\Account
 */
interface AccountChartGeneratorInterface
{
    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function expenseAccounts(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function frontpage(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function revenueAccounts(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Account $account
     * @param array   $labels
     * @param array   $dataSet
     *
     * @return array
     */
    public function single(Account $account, array $labels, array $dataSet): array;
}
