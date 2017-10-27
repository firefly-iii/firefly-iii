<?php
/**
 * TransactionCurrencies.php
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

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\TransactionCurrency;

/**
 * Class TransactionCurrencies
 *
 * @package FireflyIII\Import\Mapper
 */
class TransactionCurrencies implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap(): array
    {
        $currencies = TransactionCurrency::get();
        $list       = [];
        foreach ($currencies as $currency) {
            $list[$currency->id] = $currency->name . ' (' . $currency->code . ')';
        }

        asort($list);

        $list = [0 => trans('csv.map_do_not_map')] + $list;

        return $list;

    }
}
