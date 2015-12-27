<?php

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use Illuminate\Support\Collection;

/**
 * Interface CategoryRepositoryInterface
 *
 * @package FireflyIII\Repositories\Category
 */
interface CategoryRepositoryInterface
{
    /**
     * @param Category $category
     *
     * @return int
     */
    public function countJournals(Category $category);

    /**
     * @param Category $category
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
     * Returns a collection of Categories appended with the amount of money that has been earned
     * in these categories, based on the $accounts involved, in period X.
     * The amount earned in category X in period X is saved in field "earned".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function earnedForAccounts(Collection $accounts, Carbon $start, Carbon $end);

    /**
     * Returns a collection of Categories appended with the amount of money that has been spent
     * in these categories, based on the $accounts involved, in period X.
     * The amount earned in category X in period X is saved in field "spent".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function spentForAccounts(Collection $accounts, Carbon $start, Carbon $end);

    /**
     * @return Collection
     */
    public function getCategories();


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
     * Returns the amount spent without category by accounts in period.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentNoCategoryForAccounts(Collection $accounts, Carbon $start, Carbon $end);

    /**
     * Returns the amount earned without category by accounts in period.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedNoCategoryForAccounts(Collection $accounts, Carbon $start, Carbon $end);

    /**
     * Corrected for tags.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getCategoriesAndExpenses(Carbon $start, Carbon $end);

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
     * @return Collection
     */
    public function getJournalsInRange(Category $category, $page, Carbon $start, Carbon $end);

    /**
     * @deprecated
     * This method returns the sum of the journals in the category, optionally
     * limited by a start or end date.
     *
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return string
     */
    public function journalsSum(Category $category, Carbon $start = null, Carbon $end = null);

    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    public function getLatestActivity(Category $category);

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getWithoutCategory(Carbon $start, Carbon $end);

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
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @param bool           $shared
     *
     * @return string
     */
    public function spentInPeriod(Category $category, Carbon $start, Carbon $end);

    /**
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @param bool           $shared
     *
     * @return string
     */
    public function earnedInPeriod(Category $category, Carbon $start, Carbon $end);

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
