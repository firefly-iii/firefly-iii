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
     * @param array $entries
     *
     * @return array
     */
    public function period(array $entries) : array
    {

        $data = [
            'labels'   => array_keys($entries),
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

        foreach ($entries as $label => $entry) {
            // data set 0 is budgeted
            // data set 1 is spent:
            $data['datasets'][0]['data'][] = $entry['budgeted'];
            $data['datasets'][1]['data'][] = round(($entry['spent'] * -1), 2);

        }

        return $data;

    }
}
