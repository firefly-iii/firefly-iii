<?php
/**
 * GroupSumCollectorInterface.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Collector;

use Carbon\Carbon;
use FireflyIII\User;

/**
 * Interface GroupSumCollectorInterface
 * @codeCoverageIgnore
 */
interface GroupSumCollectorInterface
{
    /**
     * Return the final sum.
     *
     * @return array
     */
    public function getSum(): array;

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return GroupSumCollectorInterface
     */
    public function setRange(Carbon $start, Carbon $end): GroupSumCollectorInterface;

    /**
     * Reset the query.
     *
     * @return GroupSumCollectorInterface
     */
    public function resetQuery(): GroupSumCollectorInterface;

    /**
     * Limit the sum to a set of transaction types.
     *
     * @param array $types
     *
     * @return GroupSumCollectorInterface
     */
    public function setTypes(array $types): GroupSumCollectorInterface;

    /**
     * Set the user object and start the query.
     *
     * @param User $user
     *
     * @return GroupSumCollectorInterface
     */
    public function setUser(User $user): GroupSumCollectorInterface;
}