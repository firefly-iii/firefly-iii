<?php
/**
 * TransactionCurrencies.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Mapper;

use FireflyIII\Models\TransactionCurrency as TC;

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
        $currencies = TC::get();
        $list       = [];
        foreach ($currencies as $currency) {
            $list[$currency->id] = $currency->name . ' (' . $currency->code . ')';
        }

        asort($list);

        $list = [0 => trans('csv.do_not_map')] + $list;

        return $list;

    }
}
