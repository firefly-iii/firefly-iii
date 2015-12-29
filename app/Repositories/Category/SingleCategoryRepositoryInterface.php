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
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return string
     */
    public function earnedInPeriod(Category $category, Carbon $start, Carbon $end);


    /**
     * Calculate how much is earned in this period.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriodForAccounts(Category $category, Collection $accounts, Carbon $start, Carbon $end);

    /**
     *
     * Corrected for tags.
     *
     * @param Category $category
     * @param Carbon   $date
     *
     * @return float
     */
    public function earnedOnDaySum(Category $category, Carbon $date);

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
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return string
     */
    public function spentInPeriod(Category $category, Carbon $start, Carbon $end);

    /**
     * Calculates how much is spent in this period.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodForAccounts(Category $category, Collection $accounts, Carbon $start, Carbon $end);

    /**
     *
     * Corrected for tags.
     *
     * @param Category $category
     * @param Carbon   $date
     *
     * @return float
     */
    public function spentOnDaySum(Category $category, Carbon $date);

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