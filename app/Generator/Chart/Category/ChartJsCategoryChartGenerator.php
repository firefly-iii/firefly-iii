<?php
/**
 * ChartJsCategoryChartGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Generator\Chart\Category;

use FireflyIII\Support\ChartColour;
use Illuminate\Support\Collection;


/**
 * Class ChartJsCategoryChartGenerator
 *
 * @package FireflyIII\Generator\Chart\Category
 */
class ChartJsCategoryChartGenerator implements CategoryChartGeneratorInterface
{

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function all(Collection $entries): array
    {
        $data = [
            'count'    => 2,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => trans('firefly.spent'),
                    'data'  => [],
                ],
                [
                    'label' => trans('firefly.earned'),
                    'data'  => [],
                ],
            ],
        ];

        foreach ($entries as $entry) {
            $data['labels'][] = $entry[1];
            $spent            = $entry[2];
            $earned           = $entry[3];

            $data['datasets'][0]['data'][] = bccomp($spent, '0') === 0 ? null : round(bcmul($spent, '-1'), 4);
            $data['datasets'][1]['data'][] = bccomp($earned, '0') === 0 ? null : round($earned, 4);
        }

        return $data;
    }

    /**
     * @param Collection $entries
     *
     * @return array
     */
    public function frontpage(Collection $entries): array
    {
        $data = [
            'count'    => 1,
            'labels'   => [],
            'datasets' => [
                [
                    'label' => trans('firefly.spent'),
                    'data'  => [],
                ],
            ],
        ];
        foreach ($entries as $entry) {
            if ($entry->spent != 0) {
                $data['labels'][]              = $entry->name;
                $data['datasets'][0]['data'][] = round(bcmul($entry->spent, '-1'), 2);
            }
        }

        return $data;
    }

    /**
     * @param array $entries
     *
     * @return array
     */
    public function mainReportChart(array $entries): array
    {

        $data = [
            'count'    => 0,
            'labels'   => array_keys($entries),
            'datasets' => [],
        ];


        foreach ($entries as $row) {
            foreach ($row['in'] as $categoryId => $amount) {
                // get in:
                $data['datasets'][$categoryId . 'in']['data'][] = round($amount, 2);

                // get out:
                $opposite                                        = $row['out'][$categoryId];
                $data['datasets'][$categoryId . 'out']['data'][] = round($opposite, 2);

                // set name:
                $data['datasets'][$categoryId . 'out']['label'] = $row['name'][$categoryId] . ' (' . strtolower(strval(trans('firefly.expenses'))) . ')';
                $data['datasets'][$categoryId . 'in']['label']  = $row['name'][$categoryId] . ' (' . strtolower(strval(trans('firefly.income'))) . ')';

            }
        }

        // remove empty rows:
        foreach ($data['datasets'] as $key => $content) {
            if (array_sum($content['data']) === 0.0) {
                unset($data['datasets'][$key]);
            }
        }

        // re-key the datasets array:
        $data['datasets'] = array_values($data['datasets']);
        $data['count']    = count($data['datasets']);

        return $data;
    }

    /**
     *
     * @param Collection $entries
     *
     * @return array
     */
    public function period(Collection $entries): array
    {
        return $this->all($entries);

    }

    /**
     * @param array $entries
     *
     * @return array
     */
    public function reportPeriod(array $entries): array
    {

        $data = [
            'labels'   => array_keys($entries),
            'datasets' => [
                0 => [
                    'label' => trans('firefly.earned'),
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
            $data['datasets'][0]['data'][] = round($entry['earned'], 2);
            $data['datasets'][1]['data'][] = round(bcmul($entry['spent'], '-1'), 2);

        }

        return $data;

    }

    /**
     * @param array $entries
     *
     * @return array
     */
    public function pieChart(array $entries): array
    {
        $data  = [
            'datasets' => [
                0 => [],
            ],
            'labels'   => [],
        ];
        $index = 0;
        foreach ($entries as $entry) {

            if (bccomp($entry['amount'], '0') === -1) {
                $entry['amount'] = bcmul($entry['amount'], '-1');
            }

            $data['datasets'][0]['data'][]            = round($entry['amount'], 2);
            $data['datasets'][0]['backgroundColor'][] = ChartColour::getColour($index);
            $data['labels'][]                         = $entry['name'];
            $index++;
        }

        return $data;
    }

}
