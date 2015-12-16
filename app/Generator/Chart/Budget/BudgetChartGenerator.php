<?php

namespace FireflyIII\Generator\Chart\Budget;

use Illuminate\Support\Collection;

/**
 * Interface BudgetChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Budget
 */
interface BudgetChartGenerator
{
    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function budget(Collection $entries);

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYear(Collection $entries);

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function budgetLimit(Collection $entries);

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries);

    /**
     * @param Collection $budgets
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $budgets, Collection $entries);

}
