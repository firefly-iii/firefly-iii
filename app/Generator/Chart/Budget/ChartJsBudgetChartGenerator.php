<?php

namespace FireflyIII\Generator\Chart\Budget;


use Illuminate\Support\Collection;

class ChartJsBudgetChartGenerator implements BudgetChartGenerator
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function budget(Collection $entries)
    {
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function budgetLimit(Collection $entries)
    {
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries)
    {
    }

    /**
     * @param Collection $budgets
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $budgets, Collection $entries)
    {
    }
}