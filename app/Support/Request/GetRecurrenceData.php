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
    protected function getSingleTransactionData(array $transaction): array
    {
        $return = [];

        // amount + currency
        if (array_key_exists('amount', $transaction)) {
            $return['amount'] = $transaction['amount'];
        }
        if (array_key_exists('currency_id', $transaction)) {
            $return['currency_id'] = (int)$transaction['currency_id'];
        }
        if (array_key_exists('currency_code', $transaction)) {
            $return['currency_code'] = $transaction['currency_code'];
        }

        // foreign amount + currency
        if (array_key_exists('foreign_amount', $transaction)) {
            $return['foreign_amount'] = $transaction['foreign_amount'];
        }
        if (array_key_exists('foreign_currency_id', $transaction)) {
            $return['foreign_currency_id'] = (int)$transaction['foreign_currency_id'];
        }
        if (array_key_exists('foreign_currency_code', $transaction)) {
            $return['foreign_currency_code'] = $transaction['foreign_currency_code'];
        }
        // source + dest
        if (array_key_exists('source_id', $transaction)) {
            $return['source_id'] = (int)$transaction['source_id'];
        }
        if (array_key_exists('destination_id', $transaction)) {
            $return['destination_id'] = (int)$transaction['destination_id'];
        }
        // description
        if (array_key_exists('description', $transaction)) {
            $return['description'] = $transaction['description'];
        }

        if (array_key_exists('piggy_bank_id', $transaction)) {
            $return['piggy_bank_id'] = (int)$transaction['piggy_bank_id'];
        }

        if (array_key_exists('tags', $transaction)) {
            $return['tags'] = $transaction['tags'];
        }
        if (array_key_exists('budget_id', $transaction)) {
            $return['budget_id'] = (int)$transaction['budget_id'];
        }
        if (array_key_exists('category_id', $transaction)) {
            $return['category_id'] = (int)$transaction['category_id'];
        }

        return $return;
    }

}
