<?php
/**
 * MetaPieChartInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Chart;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface MetaPieChartInterface
 *
 * @package FireflyIII\Helpers\Chart
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
