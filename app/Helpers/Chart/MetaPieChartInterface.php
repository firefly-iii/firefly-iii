<?php
/**
 * MetaPieChartInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Chart;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface MetaPieChartInterface.
 */
interface MetaPieChartInterface
{
    /**
     * @param string $direction
     * @param string $group
     *
     * @return array
     */
    public function generate(string $direction, string $group): array;

    /**
     * @param Collection $accounts
     *
     * @return MetaPieChartInterface
     */
    public function setAccounts(Collection $accounts): MetaPieChartInterface;

    /**
     * @param Collection $budgets
     *
     * @return MetaPieChartInterface
     */
    public function setBudgets(Collection $budgets): MetaPieChartInterface;

    /**
     * @param Collection $categories
     *
     * @return MetaPieChartInterface
     */
    public function setCategories(Collection $categories): MetaPieChartInterface;

    /**
     * @param bool $collectOtherObjects
     *
     * @return MetaPieChartInterface
     */
    public function setCollectOtherObjects(bool $collectOtherObjects): MetaPieChartInterface;

    /**
     * @param Carbon $end
     *
     * @return MetaPieChartInterface
     */
    public function setEnd(Carbon $end): MetaPieChartInterface;

    /**
     * @param Carbon $start
     *
     * @return MetaPieChartInterface
     */
    public function setStart(Carbon $start): MetaPieChartInterface;

    /**
     * @param Collection $tags
     *
     * @return MetaPieChartInterface
     */
    public function setTags(Collection $tags): MetaPieChartInterface;

    /**
     * @param User $user
     *
     * @return MetaPieChartInterface
     */
    public function setUser(User $user): MetaPieChartInterface;
}
