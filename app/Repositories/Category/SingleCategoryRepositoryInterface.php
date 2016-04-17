<?php
declare(strict_types = 1);

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
     * @param Category $category
     *
     * @return int
     */
    public function countJournals(Category $category): int;

    /**
     * @param Category $category
     *
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return int
     */
    public function countJournalsInRange(Category $category, Carbon $start, Carbon $end): int;

    /**
     * @param Category $category
     *
     * @return bool
     */
    public function destroy(Category $category): bool;

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
    public function earnedPerDay(Category $category, Carbon $start, Carbon $end): array;

    /**
     * Find a category
     *
     * @param int $categoryId
     *
     * @return Category
     */
    public function find(int $categoryId) : Category;

    /**
     * @param Category $category
     *
     * @return Carbon
     */
    public function getFirstActivityDate(Category $category): Carbon;

    /**
     * @param Category $category
     * @param int      $page
     *
     * @return Collection
     */
    public function getJournals(Category $category, $page): Collection;

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getJournalsForAccountsInRange(Category $category, Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * @param Category $category
     * @param int      $page
     *
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return Collection
     */
    public function getJournalsInRange(Category $category, $page, Carbon $start, Carbon $end): Collection;

    /**
     * @param Category $category
     *
     * @return Carbon
     */
    public function getLatestActivity(Category $category): Carbon;

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
    public function spentPerDay(Category $category, Carbon $start, Carbon $end): array;


    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data): Category;

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     */
    public function update(Category $category, array $data): Category;
}
