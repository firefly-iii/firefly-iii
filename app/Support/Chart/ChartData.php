<?php

/*
 * ChartData.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Chart;

use FireflyIII\Exceptions\FireflyException;

class ChartData
{
    private array $series;

    public function __construct()
    {
        $this->series = [];
    }

    /**
     * @throws FireflyException
     */
    public function add(array $data): void
    {
        if (array_key_exists('currency_id', $data)) {
            $data['currency_id'] = (string) $data['currency_id'];
        }
        if (array_key_exists('native_currency_id', $data)) {
            $data['native_currency_id'] = (string) $data['native_currency_id'];
        }
        $required       = ['start', 'date', 'end', 'entries'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                throw new FireflyException(sprintf('Data-set is missing the "%s"-variable.', $field));
            }
        }

        $this->series[] = $data;
    }

    public function render(): array
    {
        if (0 === count($this->series)) {
            throw new FireflyException('No series added to chart');
        }

        return $this->series;
    }
}
