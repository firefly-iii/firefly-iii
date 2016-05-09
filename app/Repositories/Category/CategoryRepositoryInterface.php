<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface CategoryRepositoryInterface
 *
 * @package FireflyIII\Repositories\Category
 */
interface CategoryRepositoryInterface
{


    //    /**
    //     * Returns a collection of Categories appended with the amount of money that has been earned
    //     * in these categories, based on the $accounts involved, in period X, grouped per month.
    //     * The amount earned in category X in period X is saved in field "earned".
    //     *
    //     * @param $accounts
    //     * @param $start
    //     * @param $end
    //     *
    //     * @return Collection
    //     */
    //    public function earnedForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end): Collection;

    /**
     * @param Category $category
     *
     * @return bool
     */
    public function destroy(Category $category): bool;

    //    /**
    //     * This method returns a very special collection for each category:
    //     *
    //     * category, year, expense/earned, amount
    //     *
    //     * categories can be duplicated.
    //     *
    //     * @param Collection $categories
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return Collection
    //     */
    //    public function listMultiYear(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): Collection;

    //    /**
    //     * Returns a list of transaction journals in the range (all types, all accounts) that have no category
    //     * associated to them.
    //     *
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return Collection
    //     */
    //    public function listNoCategory(Carbon $start, Carbon $end): Collection;

    //    /**
    //     * Returns a collection of Categories appended with the amount of money that has been spent
    //     * in these categories, based on the $accounts involved, in period X, grouped per month.
    //     * The amount earned in category X in period X is saved in field "spent".
    //     *
    //     * @param $accounts
    //     * @param $start
    //     * @param $end
    //     *
    //     * @return Collection
    //     */
    //    public function spentForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end): Collection;

    //    /**
    //     * Returns the total amount of money related to transactions without any category connected to
    //     * it. Returns either the earned amount.
    //     *
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return string
    //     */
    //    public function sumEarnedNoCategory(Collection $accounts, Carbon $start, Carbon $end): string;

    //    /**
    //     * Returns the total amount of money related to transactions without any category connected to
    //     * it. Returns either the spent amount.
    //     *
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return string
    //     */
    //    public function sumSpentNoCategory(Collection $accounts, Carbon $start, Carbon $end): string;


    //    /**
    //     * @param Category    $category
    //     * @param Carbon|null $start
    //     * @param Carbon|null $end
    //     *
    //     * @return int
    //     */
    //    public function countJournals(Category $category, Carbon $start = null, Carbon $end = null): int;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriod(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): string;

    //    /**
    //     * Returns an array with the following key:value pairs:
    //     *
    //     * yyyy-mm-dd:<amount>
    //     *
    //     * Where yyyy-mm-dd is the date and <amount> is the money earned using DEPOSITS in the $category
    //     * from all the users accounts.
    //     *
    //     * @param Category   $category
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param Collection $accounts
    //     *
    //     * @return array
    //     */
    //    public function earnedPerDay(Category $category, Carbon $start, Carbon $end, Collection $accounts): array;

    /**
     * Find a category
     *
     * @param int $categoryId
     *
     * @return Category
     */
    public function find(int $categoryId) : Category;

    //    /**
    //     * @param Category $category
    //     *
    //     * @return Carbon
    //     */

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon
     */
    public function firstUseDate(Category $category, Collection $accounts): Carbon;

    /**
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    public function getCategories(): Collection;

    /**
     * @param Category $category
     * @param int      $page
     * @param int      $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(Category $category, int $page, int $pageSize): LengthAwarePaginator;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param array      $types
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriod(Collection $categories, Collection $accounts, array $types, Carbon $start, Carbon $end): Collection;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end) : Collection;

    /**
     * Return most recent transaction(journal) date.
     *
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon
     */
    public function lastUseDate(Category $category, Collection $accounts): Carbon;

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): string;
    //    /**
    //     * @param Category $category
    //     * @param int      $page
    //     * @param int      $pageSize
    //     *
    //     * @return Collection
    //     */
    //    public function getJournals(Category $category, int $page, int $pageSize = 50): Collection;

    //    /**
    //     * @param Category   $category
    //     * @param Collection $accounts
    //     *
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return Collection
    //     */
    //    public function getJournalsForAccountsInRange(Category $category, Collection $accounts, Carbon $start, Carbon $end): Collection;

    //    /**
    //     * @param Category $category
    //     * @param Carbon   $start
    //     * @param Carbon   $end
    //     * @param int      $page
    //     * @param int      $pageSize
    //     *
    //     *
    //     * @return Collection
    //     */
    //    public function getJournalsInRange(Category $category, Carbon $start, Carbon $end, int $page, int $pageSize = 50): Collection;

    //    /**
    //     * @param Category $category
    //     *
    //     * @return Carbon
    //     */
    //    public function getLatestActivity(Category $category): Carbon;

    //    /**
    //     * Returns an array with the following key:value pairs:
    //     *
    //     * yyyy-mm-dd:<amount>
    //     *
    //     * Where yyyy-mm-dd is the date and <amount> is the money spent using WITHDRAWALS in the $category
    //     * from all the users accounts.
    //     *
    //     * @param Category   $category
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param Collection $accounts
    //     *
    //     * @return array
    //     */
    //    public function spentPerDay(Category $category, Carbon $start, Carbon $end, Collection $accounts): array;

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
