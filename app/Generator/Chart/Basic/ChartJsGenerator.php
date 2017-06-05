<?php
/**
 * ChartJsGenerator.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Generator\Chart\Basic;

use FireflyIII\Support\ChartColour;
use Steam;

/**
 * Class ChartJsGenerator
 *
 * @package FireflyIII\Generator\Chart\Basic
 */
class ChartJsGenerator implements GeneratorInterface
{

    /**
     * Will generate a Chart JS compatible array from the given input. Expects this format
     *
     * Will take labels for all from first set.
     *
     * 0: [
     *    'label' => 'label of set',
     *    'type' => bar or line, optional
     *    'yAxisID' => ID of yAxis, optional, will not be included when unused.
     *    'fill' => if to fill a line? optional, will not be included when unused.
     *    'entries' =>
     *        [
     *         'label-of-entry' => 'value'
     *        ]
     *    ]
     * 1: [
     *    'label' => 'label of another set',
     *    'type' => bar or line, optional
     *    'yAxisID' => ID of yAxis, optional, will not be included when unused.
     *    'fill' => if to fill a line? optional, will not be included when unused.
     *    'entries' =>
     *        [
     *         'label-of-entry' => 'value'
     *        ]
     *    ]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's five.
     *
     * @param array $data
     *
     * @return array
     */
    public function multiSet(array $data): array
    {
        reset($data);
        $first  = current($data);
        $labels = is_array($first['entries']) ? array_keys($first['entries']) : [];

        $chartData = [
            'count'    => count($data),
            'labels'   => $labels, // take ALL labels from the first set.
            'datasets' => [],
        ];
        unset($first, $labels);

        foreach ($data as $set) {
            $currentSet = [
                'label' => $set['label'],
                'type'  => $set['type'] ?? 'line',
                'data'  => array_values($set['entries']),
            ];
            if (isset($set['yAxisID'])) {
                $currentSet['yAxisID'] = $set['yAxisID'];
            }
            if (isset($set['fill'])) {
                $currentSet['fill'] = $set['fill'];
            }
            if (isset($set['currency_symbol'])) {
                $currentSet['currency_symbol'] = $set['currency_symbol'];
            }

            $chartData['datasets'][] = $currentSet;
        }

        return $chartData;
    }

    /**
     * Expects data as:
     *
     * key => value
     *
     * @param array $data
     *
     * @return array
     */
    public function pieChart(array $data): array
    {
        $chartData = [
            'datasets' => [
                0 => [],
            ],
            'labels'   => [],
        ];

        // sort by value, keep keys.
        asort($data);

        $index = 0;
        foreach ($data as $key => $value) {

            // make larger than 0
            $chartData['datasets'][0]['data'][]            = floatval(Steam::positive($value));
            $chartData['datasets'][0]['backgroundColor'][] = ChartColour::getColour($index);
            $chartData['labels'][]                         = $key;
            $index++;
        }

        return $chartData;
    }

    /**
     * Will generate a (ChartJS) compatible array from the given input. Expects this format:
     *
     * 'label-of-entry' => value
     * 'label-of-entry' => value
     *
     * @param string $setLabel
     * @param array  $data
     *
     * @return array
     */
    public function singleSet(string $setLabel, array $data): array
    {
        $chartData = [
            'count'    => 1,
            'labels'   => array_keys($data), // take ALL labels from the first set.
            'datasets' => [
                [
                    'label' => $setLabel,
                    'data'  => array_values($data),
                ],
            ],
        ];

        return $chartData;
    }
}
