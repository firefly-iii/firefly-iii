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
     * @param string     $dateFormat
     *
     * @return array
     */
    public function budget(Collection $entries, $dateFormat = 'month')
    {
        // language:
        $language = Preferences::get('language', env('DEFAULT_LANGUAGE', 'en_US'))->data;
        $format   = Config::get('firefly.' . $dateFormat . '.' . $language);

        $data = [
            'labels'   => [],
            'datasets' => [
                [
                    'label' => 'Amount',
                    'data'  => [],
                ],
            ],
        ];

        /** @var array $entry */
        foreach ($entries as $entry) {
            $data['labels'][]              = $entry[0]->formatLocalized($format);
            $data['datasets'][0]['data'][] = $entry[1];

        }

        $data['count'] = count($data['datasets']);

        return $data;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param Collection $entries
     *
     * @return array
     */
    public function budgetLimit(Collection $entries)
    {
        return $this->budget($entries, 'monthAndDay');
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries)
    {
        $data = [
            'count'    => 0,
            'labels'   => [],
            'datasets' => [],
        ];
        // dataset: left
        // dataset: spent
        // dataset: overspent
        $left      = [];
        $spent     = [];
        $overspent = [];
        foreach ($entries as $entry) {
            if ($entry[1] != 0 || $entry[2] != 0 || $entry[3] != 0) {
                $data['labels'][] = $entry[0];
                $left[]           = round($entry[1], 2);
                $spent[]          = round($entry[2], 2);
                $overspent[]      = round($entry[3], 2);
            }
        }

        $data['datasets'][] = [
            'label' => trans('firefly.left'),
            'data'  => $left,
        ];
        $data['datasets'][] = [
            'label' => trans('firefly.spent'),
            'data'  => $spent,
        ];
        $data['datasets'][] = [
            'label' => trans('firefly.overspent'),
            'data'  => $overspent,
        ];

        $data['count'] = count($data['datasets']);

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
        $format = trans('config.month');

        $data = [
            'labels'   => [],
            'datasets' => [],
        ];

        foreach ($budgets as $budget) {
            $data['labels'][] = $budget->name;
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
        $data['count'] = count($data['datasets']);

        return $data;
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYear(Collection $entries)
    {
        // dataset:
        $data = [
            'count'    => 0,
            'labels'   => [],
            'datasets' => [],
        ];
        // get labels from one of the budgets (assuming there's at least one):
        $first = $entries->first();
        foreach ($first['budgeted'] as $year => $noInterest) {
            $data['labels'][] = strval($year);
        }

        // then, loop all entries and create datasets:
        foreach ($entries as $entry) {
            $name               = $entry['name'];
            $spent              = $entry['spent'];
            $budgeted           = $entry['budgeted'];
            $data['datasets'][] = ['label' => 'Spent on ' . $name, 'data' => array_values($spent)];
            $data['datasets'][] = ['label' => 'Budgeted for ' . $name, 'data' => array_values($budgeted)];
        }
        $data['count'] = count($data['datasets']);

        return $data;

    }
}
