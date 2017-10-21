<?php
/**
 * InternalTransferFilter.php
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
 * Class InternalTransferFilter
 *
 * This filter removes any filters that are from A to B or from B to A given a set of
 * account id's (in $parameters) where A and B are mentioned. So transfers between the mentioned
 * accounts will be removed.
 *
 * @package FireflyIII\Helpers\Filter
 */
class InternalTransferFilter implements FilterInterface
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
                if (is_null($transaction->opposing_account_id)) {
                    return $transaction;
                }
                // both id's in $parameters?
                if (in_array($transaction->account_id, $this->accounts) && in_array($transaction->opposing_account_id, $this->accounts)) {
                    Log::debug(
                        sprintf(
                            'Transaction #%d has #%d and #%d in set, so removed',
                            $transaction->id, $transaction->account_id, $transaction->opposing_account_id
                        ), $this->accounts
                    );

                    return false;
                }

                return $transaction;

            }
        );


    }
}
