<?php

namespace FireflyIII\Helpers\Csv\Mapper;

use FireflyIII\Models\TransactionCurrency as TC;

/**
 * Class TransactionCurrency
 *
 * @package FireflyIII\Helpers\Csv\Mapper
 */
class TransactionCurrency implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap()
    {
        $currencies = TC::get();
        $list       = [];
        foreach ($currencies as $currency) {
            $list[$currency->id] = $currency->name . ' (' . $currency->code . ')';
        }

        asort($list);

        array_unshift($list, trans('firefly.csv_do_not_map'));

        return $list;
    }
}