<?php
/**
 * Support.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Generator\Report\Category;

use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;
use Log;


/**
 * Class Support
 *
 * @package FireflyIII\Generator\Report\Category
 */
class Support
{

    /**
     * @param Collection $collection
     * @param array      $accounts
     *
     * @return Collection
     */
    public static function filterExpenses(Collection $collection, array $accounts): Collection
    {
        $result = $collection->filter(
            function (Transaction $transaction) use ($accounts) {
                $opposing = $transaction->opposing_account_id;
                // remove internal transfer
                if (in_array($opposing, $accounts)) {
                    Log::debug(sprintf('Filtered #%d because its opposite is in accounts.', $transaction->id));

                    return null;
                }
                // remove positive amount
                if (bccomp($transaction->transaction_amount, '0') === 1) {
                    Log::debug(sprintf('Filtered #%d because amount is %f.', $transaction->id, $transaction->transaction_amount));

                    return null;
                }

                return $transaction;
            }
        );

        return $result;
    }

    /**
     * @param Collection $collection
     * @param array      $accounts
     *
     * @return Collection
     */
    public static function filterIncome(Collection $collection, array $accounts): Collection
    {
        $result = $collection->filter(
            function (Transaction $transaction) use ($accounts) {
                $opposing = $transaction->opposing_account_id;
                // remove internal transfer
                if (in_array($opposing, $accounts)) {
                    Log::debug(sprintf('Filtered #%d because its opposite is in accounts.', $transaction->id));

                    return null;
                }
                // remove positive amount
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    Log::debug(sprintf('Filtered #%d because amount is %f.', $transaction->id, $transaction->transaction_amount));

                    return null;
                }

                return $transaction;
            }
        );

        return $result;
    }

}