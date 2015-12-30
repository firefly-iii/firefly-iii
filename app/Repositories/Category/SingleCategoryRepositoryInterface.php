<?php

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use Illuminate\Support\Collection;

/**
 * Interface SingleCategoryRepositoryInterface
 *
 * @package FireflyIII\Repositories\Category
 */
interface SingleCategoryRepositoryInterface
{
    /**
     * Corrected for tags and list of accounts.
     *
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @param Collection     $accounts
     *
     * @return string
     */
    public function balanceInPeriod(Category $category, Carbon $start, Carbon $end, Collection $accounts);

    /**
     * @param Category $category
     *
     * @return int
     */
    public function countJournals(Category $category);

    /**
     * @param Category $category
     *
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return int
     */
    public function countJournalsInRange(Category $category, Carbon $start, Carbon $end);

    /**
     * @param Category $category
     *
     * @return boolean
     */
    public function destroy(Category $category);

    /**
     * @deprecated
     *
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return string
     */
    public function earnedInPeriod(Category $category, Carbon $start, Carbon $end);

    /**
     * Returns an array with the following key:value pairs:
     *
     * yyyy-mm-dd:<amount>
     *
     * Where yyyy-mm-dd is the date and <amount> is the money earned using DEPOSITS in the $category
     * from all the users accounts.
     *
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    public function earnedPerDay(Category $category, Carbon $start, Carbon $end);


    /**
     * @param Category $category
     *
     * @return Carbon
     */
    public function getFirstActivityDate(Category $category);

    /**
     * @param Category $category
     * @param int      $page
     *
     * @return Collection
     */
    public function getJournals(Category $category, $page);


    /**
     * @param Category $category
     * @param int      $page
     *
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return Collection
     */
    public function getJournalsInRange(Category $category, $page, Carbon $start, Carbon $end);

    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    public function getLatestActivity(Category $category);

    /**
     * @deprecated
     *
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return string
     */
    public function spentInPeriod(Category $category, Carbon $start, Carbon $end);

    /**
     * Returns an array with the following key:value pairs:
     *
     * yyyy-mm-dd:<amount>
     *
     * Where yyyy-mm-dd is the date and <amount> is the money spent using WITHDRAWALS in the $category
     * from all the users accounts.
     *
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    public function spentPerDay(Category $category, Carbon $start, Carbon $end);


    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data);

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     */
    public function update(Category $category, array $data);
}