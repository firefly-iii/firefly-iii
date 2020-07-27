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
    /**
     * @param array $transaction
     *
     * @return array
     */
    protected function getSingleRecurrenceData(array $transaction): array
    {
        return [
            'amount'                => $transaction['amount'],
            'currency_id'           => isset($transaction['currency_id']) ? (int) $transaction['currency_id'] : null,
            'currency_code'         => $transaction['currency_code'] ?? null,
            'foreign_amount'        => $transaction['foreign_amount'] ?? null,
            'foreign_currency_id'   => isset($transaction['foreign_currency_id']) ? (int) $transaction['foreign_currency_id'] : null,
            'foreign_currency_code' => $transaction['foreign_currency_code'] ?? null,
            'source_id'             => isset($transaction['source_id']) ? (int) $transaction['source_id'] : null,
            'source_name'           => isset($transaction['source_name']) ? (string) $transaction['source_name'] : null,
            'destination_id'        => isset($transaction['destination_id']) ? (int) $transaction['destination_id'] : null,
            'destination_name'      => isset($transaction['destination_name']) ? (string) $transaction['destination_name'] : null,
            'description'           => $transaction['description'],
            'type'                  => $this->string('type'),

            // new and updated fields:
            'piggy_bank_id'         => isset($transaction['piggy_bank_id']) ? (int) $transaction['piggy_bank_id'] : null,
            'piggy_bank_name'       => $transaction['piggy_bank_name'] ?? null,
            'tags'                  => $transaction['tags'] ?? [],
            'budget_id'             => isset($transaction['budget_id']) ? (int) $transaction['budget_id'] : null,
            'budget_name'           => $transaction['budget_name'] ?? null,
            'category_id'           => isset($transaction['category_id']) ? (int) $transaction['category_id'] : null,
            'category_name'         => $transaction['category_name'] ?? null,
        ];
    }

}
