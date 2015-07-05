<?php

namespace FireflyIII\Helpers\Csv;

use Auth;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class DataGrabber
 *
 * Class dedicated to retreiving all sorts of data related to the CSV import.
 *
 * @package FireflyIII\Helpers\Csv
 */
class DataGrabber
{

    /**
     * @return array
     */
    public function getAssetAccounts()
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

        return $list;
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        $currencies = TransactionCurrency::get();
        $list       = [];
        foreach ($currencies as $currency) {
            $list[$currency->id] = $currency->name . ' (' . $currency->code . ')';
        }

        return $list;
    }

}