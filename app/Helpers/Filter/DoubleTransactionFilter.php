<?php
/**
 * DoubleTransactionFilter.php
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
use Illuminate\Support\Collection;

/**
 *
 * Used when the final collection contains double transactions, which can happen when viewing the tag report.
 * Class DoubleTransactionFilter
 *
 * @codeCoverageIgnore
 */
class DoubleTransactionFilter implements FilterInterface
{

    /**
     * Apply the filter.
     *
     * @param Collection $set
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        $count  = [];
        $result = new Collection;
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $id         = (int)$transaction->id;
            $count[$id] = isset($count[$id]) ? $count[$id] + 1 : 1;
            if (1 === $count[$id]) {
                $result->push($transaction);
            }
        }

        return $result;
    }
}
