<?php
/**
 * OpposingAccountFilter.php
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
 * Class OpposingAccountFilter.
 *
 * This filter is similar to the internal transfer filter but only removes transactions when the opposing account is
 * amongst $parameters (list of account ID's).
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
