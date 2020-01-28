<?php
/**
 * GeneratorInterface.php
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

/**
 * Interface GeneratorInterface.
 */
interface GeneratorInterface
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function multiCurrencyPieChart(array $data): array;

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
    public function multiSet(array $data): array;

    /**
     * Expects data as:.
     *
     * key => value
     *
     * @param array $data
     *
     * @return array
     */
    public function pieChart(array $data): array;

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
    public function singleSet(string $setLabel, array $data): array;
}
