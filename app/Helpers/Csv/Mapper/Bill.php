<?php

namespace FireflyIII\Helpers\Csv\Mapper;

use Auth;
use FireflyIII\Models\Bill as BillModel;

/**
 * Class Bill
 *
 * @package FireflyIII\Helpers\Csv\Mapper
 */
class Bill implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap()
    {
        $result = Auth::user()->bills()->get(['bills.*']);
        $list   = [];

        /** @var BillModel $bill */
        foreach ($result as $bill) {
            $list[$bill->id] = $bill->name . ' [' . $bill->match . ']';
        }
        asort($list);

        $list = [0 => trans('firefly.csv_do_not_map')] + $list;

        return $list;
    }
}
