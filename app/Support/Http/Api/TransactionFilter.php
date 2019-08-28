<?php
/**
 * TransactionFilter.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Http\Api;

use FireflyIII\Models\TransactionType;

/**
 * Trait TransactionFilter
 * @codeCoverageIgnore
 */
trait TransactionFilter
{
    /**
     * All the types you can request.
     *
     * @param string $type
     *
     * @return array
     */
    protected function mapTransactionTypes(string $type): array
    {
        $types  = [
            'all'             => [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE,
                                  TransactionType::RECONCILIATION,],
            'withdrawal'      => [TransactionType::WITHDRAWAL,],
            'withdrawals'     => [TransactionType::WITHDRAWAL,],
            'expense'         => [TransactionType::WITHDRAWAL,],
            'expenses'        => [TransactionType::WITHDRAWAL,],
            'income'          => [TransactionType::DEPOSIT,],
            'deposit'         => [TransactionType::DEPOSIT,],
            'deposits'        => [TransactionType::DEPOSIT,],
            'transfer'        => [TransactionType::TRANSFER,],
            'transfers'       => [TransactionType::TRANSFER,],
            'opening_balance' => [TransactionType::OPENING_BALANCE,],
            'reconciliation'  => [TransactionType::RECONCILIATION,],
            'reconciliations' => [TransactionType::RECONCILIATION,],
            'special'         => [TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,],
            'specials'        => [TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,],
            'default'         => [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER,],
        ];
        return $types[$type] ?? $types['default'];


    }
}
