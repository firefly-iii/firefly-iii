<?php

/**
 * GetRecurrenceData.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\Request;

/**
 * Trait GetRecurrenceData
 */
trait GetRecurrenceData
{
    protected function getSingleTransactionData(array $transaction): array
    {
        $return     = [];
        $stringKeys = ['id'];
        $intKeys    = ['currency_id', 'foreign_currency_id', 'source_id', 'destination_id', 'bill_id', 'piggy_bank_id', 'bill_id', 'budget_id', 'category_id'];
        $keys       = ['amount', 'currency_code', 'foreign_amount', 'foreign_currency_code', 'description', 'tags'];

        foreach ($stringKeys as $key) {
            if (array_key_exists($key, $transaction)) {
                $return[$key] = (string) $transaction[$key];
            }
        }
        foreach ($intKeys as $key) {
            if (array_key_exists($key, $transaction)) {
                $return[$key] = (int) $transaction[$key];
            }
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $transaction)) {
                $return[$key] = $transaction[$key];
            }
        }

        return $return;
    }
}
