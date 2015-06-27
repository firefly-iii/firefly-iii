<?php

namespace FireflyIII\Generator\Chart\Budget;


use Illuminate\Support\Collection;

/**
 * Class ChartJsBudgetChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Budget
 */
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
        $data = [
            'count'    => 2,
            'labels'   => [],
            'datasets' => [],
        ];
        foreach ($entries as $entry) {
            if ($entry[1] != 0 || $entry[2] != 0 || $entry[3] != 0) {
                $data['labels'][] = $entry[0];
            }
        }
        // dataset: left
        // dataset: spent
        // dataset: overspent
        $left      = [];
        $spent     = [];
        $overspent = [];
        $amount    = [];
        $expenses = [];
        foreach ($entries as $entry) {
            if ($entry[1] != 0 || $entry[2] != 0 || $entry[3] != 0) {
                $left[]      = round($entry[1], 2);
                $spent[]     = round($entry[2], 2);
                $overspent[] = round($entry[3], 2);
                $amount[]    = round($entry[4], 2);
                $expenses[]    = round($entry[5], 2);
                //$data['count']++;
            }
        }

        $data['datasets'][] = [
            'label' => 'Amount',
            'data'  => $amount,
        ];
        $data['datasets'][] = [
            'label' => 'Spent',
            'data'  => $expenses,
        ];

        return $data;
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