<?php

/*
 * CleansChartData.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Support\Http\Api;

use FireflyIII\Exceptions\FireflyException;

/**
 * Trait CleansChartData
 */
trait CleansChartData
{
    /**
     * Clean up given chart data array. Each entry is supposed to be a
     * "main" entry used in the V2 API chart endpoints. This loop makes sure
     * IDs are strings and other values are present (or missing).
     *
     * @throws FireflyException
     */
    private function clean(array $data): array
    {
        $return = [];

        /**
         * @var mixed $index
         * @var array $array
         */
        foreach ($data as $index => $array) {
            if (array_key_exists('currency_id', $array)) {
                $array['currency_id'] = (string) $array['currency_id'];
            }
            if (array_key_exists('native_currency_id', $array)) {
                $array['native_currency_id'] = (string) $array['native_currency_id'];
            }
            if (!array_key_exists('start', $array)) {
                throw new FireflyException(sprintf('Data-set "%s" is missing the "start"-variable.', $index));
            }
            if (!array_key_exists('end', $array)) {
                throw new FireflyException(sprintf('Data-set "%s" is missing the "end"-variable.', $index));
            }
            if (!array_key_exists('period', $array)) {
                throw new FireflyException(sprintf('Data-set "%s" is missing the "period"-variable.', $index));
            }
            $return[] = $array;
        }

        return $return;
    }
}
