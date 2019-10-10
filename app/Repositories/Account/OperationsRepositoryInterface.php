<?php
/**
 * OperationsRepositoryInterface.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface OperationsRepositoryInterface
 */
interface OperationsRepositoryInterface
{
    /**
     * This method returns a list of all the withdrawal transaction journals (as arrays) set in that period
     * which have the specified accounts. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function listExpenses(Carbon $start, Carbon $end, Collection $accounts): array;

    /**
     * This method returns a list of all the deposit transaction journals (as arrays) set in that period
     * which have the specified accounts. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     *
     * @return array
     */
    public function listIncome(Carbon $start, Carbon $end, ?Collection $accounts = null): array;

    /**
     * @param User $user
     */
    public function setUser(User $user): void;

    /**
     * Sum of withdrawal journals in period for a set of accounts, grouped per currency. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     *
     * @return array
     */
    public function sumExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null): array;

    /**
     * Sum of income journals in period for a set of accounts, grouped per currency. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     *
     * @return array
     */
    public function sumIncome(Carbon $start, Carbon $end, ?Collection $accounts = null): array;
}