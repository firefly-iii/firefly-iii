<?php

namespace FireflyIII\Generator\Chart\Budget;


use Config;
use Illuminate\Support\Collection;
use Preferences;

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
        $data = [
            'count'    => 1,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => 'Amount',
                    'data'  => [],
                ]
            ],
        ];

        // language:
        $language = Preferences::get('language', 'en')->data;
        $format   = Config::get('firefly.month.' . $language);

        /** @var array $entry */
        foreach ($entries as $entry) {
            $data['labels'][]              = $entry[0]->formatLocalized($format);
            $data['datasets'][0]['data'][] = $entry[1];

        }

        return $data;
    }

    /**
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
        $expenses  = [];
        foreach ($entries as $entry) {
            if ($entry[1] != 0 || $entry[2] != 0 || $entry[3] != 0) {
                $left[]      = round($entry[1], 2);
                $spent[]     = round($entry[2], 2);
                $overspent[] = round($entry[3], 2);
                $amount[]    = round($entry[4], 2);
                $expenses[]  = round($entry[5], 2);
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
        // language:
        $language = Preferences::get('language', 'en')->data;
        $format   = Config::get('firefly.month.' . $language);

        $data = [
            'count'    => 0,
            'labels'   => [],
            'datasets' => [],
        ];

        foreach ($budgets as $budget) {
            $data['labels'][] = $budget->name;
            $data['count']++;
        }
        /** @var array $entry */
        foreach ($entries as $entry) {
            $array = [
                'label' => $entry[0]->formatLocalized($format),
                'data'  => [],
            ];
            array_shift($entry);
            $array['data']      = $entry;
            $data['datasets'][] = $array;

        }

        return $data;


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