<?php
/**
 * ChartJsGenerator.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Generator\Chart\Basic;

use FireflyIII\Support\ChartColour;
use Log;

/**
 * Class ChartJsGenerator.
 */
class ChartJsGenerator implements GeneratorInterface
{
    /**
     * Constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * Expects data as:.
     *
     * key => [value => x, 'currency_symbol' => 'x']
     *
     * @param array $data
     *
     * @return array
     */
    public function multiCurrencyPieChart(array $data): array
    {
        $chartData = [
            'datasets' => [
                0 => [],
            ],
            'labels'   => [],
        ];

        $amounts  = array_column($data, 'amount');
        $next     = next($amounts);
        $sortFlag = SORT_ASC;
        if (!is_bool($next) && 1 === bccomp((string)$next, '0')) {
            $sortFlag = SORT_DESC;
        }
        array_multisort($amounts, $sortFlag, $data);
        unset($next, $sortFlag, $amounts);

        $index = 0;
        foreach ($data as $key => $valueArray) {
            // make larger than 0
            $chartData['datasets'][0]['data'][]            = (float)app('steam')->positive((string)$valueArray['amount']);
            $chartData['datasets'][0]['backgroundColor'][] = ChartColour::getColour($index);
            $chartData['datasets'][0]['currency_symbol'][] = $valueArray['currency_symbol'];
            $chartData['labels'][]                         = $key;
            ++$index;
        }

        return $chartData;
    }

    /**
     * Will generate a Chart JS compatible array from the given input. Expects this format.
     *
     * Will take labels for all from first set.
     *
     * 0: [
     *    'label' => 'label of set',
     *    'type' => bar or line, optional
     *    'yAxisID' => ID of yAxis, optional, will not be included when unused.
     *    'fill' => if to fill a line? optional, will not be included when unused.
     *    'currency_symbol' => 'x',
     *    'backgroundColor' => 'x',
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
     *  // it's five.
     *
     * @param array $data
     *
     * @return array
     */
    public function multiSet(array $data): array
    {
        reset($data);
        $first = current($data);
        if (!is_array($first)) {
            return [];
        }
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
            if (isset($set['backgroundColor'])) {
                $currentSet['backgroundColor'] = $set['backgroundColor'];
            }
            $chartData['datasets'][] = $currentSet;
        }

        return $chartData;
    }

    /**
     * Expects data as:.
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
        // different sort when values are positive and when they're negative.
        asort($data);
        $next = next($data);
        if (!is_bool($next) && 1 === bccomp((string)$next, '0')) {
            // next is positive, sort other way around.
            arsort($data);
        }
        unset($next);

        $index = 0;
        foreach ($data as $key => $value) {
            // make larger than 0
            $chartData['datasets'][0]['data'][]            = (float)app('steam')->positive((string)$value);
            $chartData['datasets'][0]['backgroundColor'][] = ChartColour::getColour($index);

            $chartData['labels'][] = $key;
            ++$index;
        }

        return $chartData;
    }

    /**
     * Will generate a (ChartJS) compatible array from the given input. Expects this format:.
     *
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
