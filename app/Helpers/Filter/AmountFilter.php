<?php
/**
 * AmountFilter.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Filter;

use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AmountFilter
 *
 * This filter removes transactions with either a positive amount ($parameters = 1) or a negative amount
 * ($parameter = -1). This is helpful when a Collection has you with both transactions in a journal.
 *
 * @package FireflyIII\Helpers\Filter
 */
class AmountFilter implements FilterInterface
{

    /**
     * @param Collection $set
     * @param null       $parameters
     *
     * @return Collection
     */
    public function filter(Collection $set, $parameters = null): Collection
    {
        return $set->filter(
            function (Transaction $transaction) use ($parameters) {
                // remove by amount
                if (bccomp($transaction->transaction_amount, '0') === $parameters) {
                    Log::debug(sprintf('Filtered #%d because amount is %f.', $transaction->id, $transaction->transaction_amount));

                    return null;
                }

                return $transaction;
            }
        );
    }
}