<?php

namespace FireflyIII\Helpers\Csv\Mapper;

use Auth;
use FireflyIII\Models\Account;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class AssetAccount
 *
 * @package FireflyIII\Helpers\Csv\Mapper
 */
class AssetAccount implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap()
    {
        $result = Auth::user()->accounts()->with(
            ['accountmeta' => function (HasMany $query) {
                $query->where('name', 'accountRole');
            }]
        )->accountTypeIn(['Default account', 'Asset account'])->orderBy('accounts.name', 'ASC')->get(['accounts.*']);

        $list = [];
        /** @var Account $account */
        foreach ($result as $account) {
            $list[$account->id] = $account->name;
        }

        asort($list);

        return $list;
    }
}