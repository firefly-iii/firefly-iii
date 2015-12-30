<?php

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Sql\Query;
use Illuminate\Support\Collection;

/**
 * Interface CategoryRepositoryInterface
 *
 * @package FireflyIII\Repositories\Category
 */
interface CategoryRepositoryInterface
{


    /**
     * Returns a collection of Categories appended with the amount of money that has been earned
     * in these categories, based on the $accounts involved, in period X, grouped per month.
     * The amount earned in category X in period X is saved in field "earned".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function earnedForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end);

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
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    public function listCategories();

    /**
     * Returns a list of transaction journals in the range (all types, all accounts) that have no category
     * associated to them.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function listNoCategory(Carbon $start, Carbon $end);

    /**
     * Returns a collection of Categories appended with the amount of money that has been spent
     * in these categories, based on the $accounts involved, in period X, grouped per month.
     * The amount earned in category X in period X is saved in field "spent".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function spentForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end);

    /**
     * Returns the total amount of money related to transactions without any category connected to
     * it. Returns either the spent amount.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function sumSpentNoCategory(Collection $accounts, Carbon $start, Carbon $end);

    /**
     * Returns the total amount of money related to transactions without any category connected to
     * it. Returns either the earned amount.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function sumEarnedNoCategory(Collection $accounts, Carbon $start, Carbon $end);

}
