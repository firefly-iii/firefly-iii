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
     * @return boolean
     */
    public function destroy(Category $category);

    /**
     * @return Collection
     */
    public function getCategories();

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getCategoriesAndExpenses($start, $end);

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
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return float
     */
    public function spentInPeriod(Category $category, Carbon $start, Carbon $end, $shared = false);

    /**
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
