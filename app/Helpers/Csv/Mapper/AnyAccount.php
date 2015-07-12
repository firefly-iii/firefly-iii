<?php

namespace FireflyIII\Helpers\Csv\Mapper;

use Auth;
use FireflyIII\Models\Account;

/**
 * Class AnyAccount
 *
 * @package FireflyIII\Helpers\Csv\Mapper
 */
class AnyAccount implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap()
    {
        $result = Auth::user()->accounts()->with('accountType')->orderBy('accounts.name', 'ASC')->get(['accounts.*']);

        $list = [];
        /** @var Account $account */
        foreach ($result as $account) {
            $list[$account->id] = $account->name . ' (' . $account->accountType->type . ')';
        }
        asort($list);

        $list = [0 => trans('firefly.csv_do_not_map')] + $list;

        return $list;
    }
}
