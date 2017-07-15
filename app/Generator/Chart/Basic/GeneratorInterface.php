<?php
/**
 * GeneratorInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Generator\Chart\Basic;

/**
 * Interface GeneratorInterface
 *
 * @package FireflyIII\Generator\Chart\Basic
 */
interface GeneratorInterface
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
    public function multiSet(array $data): array;

    /**
     * Expects data as:
     *
     * key => value
     *
     * @param array $data
     *
     * @return array
     */
    public function pieChart(array $data): array;

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
    public function singleSet(string $setLabel, array $data): array;

}
