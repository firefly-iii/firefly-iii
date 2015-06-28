<?php

namespace FireflyIII\Generator\Chart\Budget;

use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;

/**
 * Class GoogleBudgetChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Budget
 */
class GoogleBudgetChartGenerator implements BudgetChartGenerator
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function budget(Collection $entries)
    {

        $chart = new GChart;
        $chart->addColumn(trans('firefly.period'), 'date');
        $chart->addColumn(trans('firefly.spent'), 'number');

        /** @var array $entry */
        foreach ($entries as $entry) {
            $chart->addRow($entry[0], $entry[1]);
        }

        $chart->generate();

        return $chart->getData();
    }

    /**
     * @codeCoverageIgnore
     * @param Collection $entries
     *
     * @return array
     */
    public function budgetLimit(Collection $entries)
    {
        return $this->budget($entries);
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries)
    {
        $chart = new GChart;
        $chart->addColumn(trans('firefly.budget'), 'string');
        $chart->addColumn(trans('firefly.left'), 'number');
        $chart->addColumn(trans('firefly.spent'), 'number');
        $chart->addColumn(trans('firefly.overspent'), 'number');

        /** @var array $entry */
        foreach ($entries as $entry) {
            if ($entry[1] != 0 || $entry[2] != 0 || $entry[3] != 0) {
                $chart->addRow($entry[0], $entry[1], $entry[2], $entry[3]);
            }
        }


        $chart->generate();

        return $chart->getData();

    }

    /**
     * @param Collection $budgets
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $budgets, Collection $entries)
    {
        $chart = new GChart;
        // add columns:
        $chart->addColumn(trans('firefly.month'), 'date');
        foreach ($budgets as $budget) {
            $chart->addColumn($budget->name, 'number');
        }

        /** @var array $entry */
        foreach ($entries as $entry) {

            $chart->addRowArray($entry);
        }

        $chart->generate();

        return $chart->getData();
    }
}
