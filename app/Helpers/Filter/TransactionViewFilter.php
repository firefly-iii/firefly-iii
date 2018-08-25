<?php
/**
 * TransactionViewFilter.php
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

namespace FireflyIII\Helpers\Filter;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionViewFilter.
 *
 * This filter removes the entry with a negative amount when it's a withdrawal
 * And the positive amount when it's a deposit or transfer
 *
 * This is used in the mass-edit routine.
 *
 * @codeCoverageIgnore
 *
 */
class TransactionViewFilter implements FilterInterface
{
    /**
     * See class description.
     *
     * @param Collection $set
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        return $set->filter(
            function (Transaction $transaction) {
                // remove if amount is less than zero and type is withdrawal.
                if ($transaction->transaction_type_type === TransactionType::WITHDRAWAL && 1 === bccomp($transaction->transaction_amount, '0')) {
                    Log::debug(
                        sprintf(
                            'Filtered #%d because amount is %f and type is %s.', $transaction->id, $transaction->transaction_amount,
                            $transaction->transaction_type_type
                        )
                    );

                    return null;
                }

                if ($transaction->transaction_type_type === TransactionType::DEPOSIT && -1 === bccomp($transaction->transaction_amount, '0')) {
                    Log::debug(
                        sprintf(
                            'Filtered #%d because amount is %f and type is %s.', $transaction->id, $transaction->transaction_amount,
                            $transaction->transaction_type_type
                        )
                    );

                    return null;
                }
                Log::debug(
                    sprintf('#%d: amount is %f and type is %s.', $transaction->id, $transaction->transaction_amount, $transaction->transaction_type_type)
                );

                return $transaction;
            }
        );
    }
}
