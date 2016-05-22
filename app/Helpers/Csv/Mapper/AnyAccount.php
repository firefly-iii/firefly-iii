<?php
/**
 * AnyAccount.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
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
    public function getMap(): array
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
