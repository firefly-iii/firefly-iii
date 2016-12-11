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

declare(strict_types = 1);

namespace FireflyIII\Generator\Chart\Basic;

/**
 * Class ChartJsGenerator
 *
 * @package FireflyIII\Generator\Chart\Basic
 */
class ChartJsGenerator implements GeneratorInterface
{

    /**
     * Will generate a Chart JS compatible array from the given input. Expects this format:
     *
     * 0: [
     *    'label' => 'label of set',
     *    'entries' =>
     *        [
     *         'label-of-entry' => 'value'
     *        ]
     *    ]
     * 1: [
     *    'label' => 'label of another set',
     *    'entries' =>
     *        [
     *         'label-of-entry' => 'value'
     *        ]
     *    ]
     *
     *
     * @param array $data
     *
     * @return array
     */
    public function multiSet(array $data): array
    {
        $chartData = [
            'count'    => count($data),
            'labels'   => array_keys($data[0]['entries']), // take ALL labels from the first set.
            'datasets' => [],
        ];

        foreach ($data as $set) {
            $chartData['datasets'][] = [
                'label' => $set['label'],
                'data'  => array_values($set['entries']),
            ];
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