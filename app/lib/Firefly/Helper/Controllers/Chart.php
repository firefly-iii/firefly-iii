<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 29-7-14
 * Time: 10:41
 */

namespace Firefly\Helper\Controllers;

use Carbon\Carbon;

class Chart implements ChartInterface
{

    public function account(\Account $account)
    {
        $data = [
            'chart_title' => $account->name,
            'subtitle' => '<a href="' . route('accounts.show', [$account->id]) . '">View more</a>',
            'series' => [$this->_account($account)]
        ];
        return $data;
    }

    public function accounts()
    {
        $data = [
            'chart_title' => 'All accounts',
            'subtitle' => '<a href="' . route('accounts.index') . '">View more</a>',
            'series' => []
        ];
        /** @var  \Firefly\Helper\Preferences\PreferencesHelperInterface $prefs */
        $prefs = \App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
        $pref = $prefs->get('frontpageAccounts', []);

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $acct */
        $acct = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        if ($pref->data == []) {
            $accounts = $acct->getActiveDefault();
        } else {
            $accounts = $acct->getByIds($pref->data);
        }
        foreach($accounts as $account) {
            $data['series'][] = $this->_account($account);
        }
        return $data;

    }

    protected function _account(\Account $account)
    {
        $start = \Session::get('start');
        $end = \Session::get('end');
        $current = clone $start;
        $today = new Carbon;
        $return = ['name' => $account->name, 'id' => $account->id, 'data' => []];
        while ($current <= $end) {
            if ($current > $today) {
                $return['data'][] = [$current->timestamp * 1000, $account->predict(clone $current)];
            } else {
                $return['data'][] = [$current->timestamp * 1000, $account->balance(clone $current)];
            }

            $current->addDay();
        }
        return $return;
    }

}