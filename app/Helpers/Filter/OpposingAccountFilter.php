<?php
/**
 * OpposingAccountFilter.php
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
 * Class OpposingAccountFilter
 *
 * This filter is similar to the internal transfer filter but only removes transactions when the opposing account is
 * amongst $parameters (list of account ID's).
 *
 * @package FireflyIII\Helpers\Filter
 */
class OpposingAccountFilter implements FilterInterface
{
    /** @var array */
    private $accounts = [];

    /**
     * InternalTransferFilter constructor.
     *
     * @param array $accounts
     */
    public function __construct(array $accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * @param Collection $set
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        return $set->filter(
            function (Transaction $transaction) {
                $opposing = $transaction->opposing_account_id;
                // remove internal transfer
                if (in_array($opposing, $this->accounts)) {
                    Log::debug(sprintf('Filtered #%d because its opposite is in accounts.', $transaction->id), $this->accounts);

                    return null;
                }

                return $transaction;
            }
        );
    }
}
