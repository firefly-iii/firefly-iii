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

declare(strict_types=1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\User;
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
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getAccountReport(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getExpenseReport(Carbon $start, Carbon $end, Collection $accounts): array;

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getIncomeReport(Carbon $start, Carbon $end, Collection $accounts): array;

    /**
     * @param User $user
     */
    public function setUser(User $user);

}
