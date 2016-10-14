<?php
/**
 * ChartJsBudgetChartGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Generator\Chart\Budget;


use Illuminate\Support\Collection;
use Navigation;

/**
 * Class ChartJsBudgetChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Budget
 */
class ChartJsBudgetChartGenerator implements BudgetChartGeneratorInterface
{
    /**
     *
     * @param Collection $entries
     * @param string     $dateFormat
     *
     * @return array
     */
    public function budgetLimit(Collection $entries, string $dateFormat = 'month_and_day'): array
    {
        $format = strval(trans('config.' . $dateFormat));
        $data   = [
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
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries): array
    {
        $data      = [
            'count'    => 0,
            'labels'   => [],
            'datasets' => [],
        ];
        $left      = [];
        $spent     = [];
        $overspent = [];
        $filtered  = $entries->filter(
            function ($entry) {
                return ($entry[1] != 0 || $entry[2] != 0 || $entry[3] != 0);
            }
        );
        foreach ($filtered as $entry) {
            $data['labels'][] = $entry[0];
            $left[]           = round($entry[1], 2);
            $spent[]          = round(bcmul($entry[2], '-1'), 2); // spent is coming in negative, must be positive
            $overspent[]      = round(bcmul($entry[3], '-1'), 2); // same
        }

        $data['datasets'][] = [
            'label' => trans('firefly.overspent'),
            'data'  => $overspent,
        ];
        $data['datasets'][] = [
            'label' => trans('firefly.left'),
            'data'  => $left,
        ];
        $data['datasets'][] = [
            'label' => trans('firefly.spent'),
            'data'  => $spent,
        ];

        $data['count'] = 3;

        return $data;
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function multiYear(Collection $entries): array
    {
        // dataset:
        $data = [
            'count'    => 0,
            'labels'   => [],
            'datasets' => [],
        ];
        // get labels from one of the budgets (assuming there's at least one):
        $first = $entries->first();
        $keys  = array_keys($first['budgeted']);
        foreach ($keys as $year) {
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

    /**
     * @param Collection $entries
     * @param string     $viewRange
     *
     * @return array
     */
    public function period(Collection $entries, string $viewRange) : array
    {
        $data = [
            'labels'   => [],
            'datasets' => [
                0 => [
                    'label' => trans('firefly.budgeted'),
                    'data'  => [],
                ],
                1 => [
                    'label' => trans('firefly.spent'),
                    'data'  => [],
                ],
            ],
            'count'    => 2,
        ];
        foreach ($entries as $entry) {
            $label            = Navigation::periodShow($entry['date'], $viewRange);
            $data['labels'][] = $label;
            // data set 0 is budgeted
            // data set 1 is spent:
            $data['datasets'][0]['data'][] = $entry['budgeted'];
            $data['datasets'][1]['data'][] = round(($entry['spent'] * -1), 2);

        }

        return $data;

    }

    /**
     * @param Collection $budgets
     * @param Collection $entries
     *
     * @return array
     */
    public function year(Collection $budgets, Collection $entries): array
    {
        // language:
        $format = (string)trans('config.month');

        $data = [
            'labels'   => [],
            'datasets' => [],
        ];

        foreach ($budgets as $budget) {
            $data['labels'][] = $budget->name;
        }
        // also add "no budget"
        $data['labels'][] = strval(trans('firefly.no_budget'));

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
}
