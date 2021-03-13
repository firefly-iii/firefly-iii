<?php
/*
 * RandomValues.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace Tests\Traits;


use Carbon\Carbon;

/**
 * Trait RandomValues
 */
trait RandomValues
{
    /**
     * @param $k
     * @param $xs
     *
     * @return array|array[]
     */
    protected function combinationsOf($k, $xs): array
    {
        if ($k === 0) {
            return [[]];
        }
        if (count($xs) === 0) {
            return [];
        }
        $x    = $xs[0];
        $xs1  = array_slice($xs, 1, count($xs) - 1);
        $res1 = $this->combinationsOf($k - 1, $xs1);
        for ($i = 0; $i < count($res1); $i++) {
            array_splice($res1[$i], 0, 0, $x);
        }
        $res2 = $this->combinationsOf($k, $xs1);

        return array_merge($res1, $res2);
    }


    /**
     * @return string
     */
    protected function randomAccountRole(): string
    {
        return $this->randomFromArray(['defaultAsset', 'sharedAsset', 'savingAsset']);
    }

    /**
     * @return string
     */
    protected function randomLiabilityType(): string
    {
        return $this->randomFromArray(['loan', 'debt', 'mortgage']);
    }

    /**
     * @return string
     */
    protected function getRandomCurrencyCode(): string
    {
        return $this->randomFromArray(['EUR', 'USD', 'GBP']);
    }

    /**
     * @return string
     */
    protected function getRandomAmount(): string
    {
        return number_format(rand(1000, 100000) / 100, '2', '.');
    }

    /**
     * @return string
     */
    protected function getRandomDateString(): string
    {
        $date = Carbon::now();
        $date->subDays(rand(10, 100));

        return $date->format('Y-m-d');
    }

    /**
     * @return string
     */
    protected function getRandomPercentage(): string
    {
        return rand(1, 10000) / 100;
    }

    /**
     * @return string
     */
    protected function getRandomInterestPeriod(): string
    {
        return $this->randomFromArray(['daily', 'monthly', 'yearly']);
    }

    /**
     * @param array $array
     *
     * @return mixed
     */
    private function randomFromArray(array $array)
    {
        return $array[rand(0, count($array) - 1)];
    }
}