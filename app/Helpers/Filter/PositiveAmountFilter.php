<?php
/**
 * PositiveAmountFilter.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Filter;

use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;
use Log;

/**
 * Class PositiveAmountFilter.
 *
 * This filter removes entries with a negative amount (the original modifier is -1).
 *
 * This filter removes transactions with either a positive amount ($parameters = 1) or a negative amount
 * ($parameter = -1). This is helpful when a Collection has you with both transactions in a journal.
 */
class PositiveAmountFilter implements FilterInterface
{
    /**
     * @param Collection $set
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        return $set->filter(
            function (Transaction $transaction) {
                // remove by amount
                if (1 === bccomp($transaction->transaction_amount, '0')) {
                    Log::debug(sprintf('Filtered #%d because amount is %f.', $transaction->id, $transaction->transaction_amount));

                    return null;
                }

                return $transaction;
            }
        );
    }
}
