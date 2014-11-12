<?php

/**
 * Class HomeController
 *
 */
class HomeController extends BaseController
{
    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function flush()
    {
        Cache::flush();

        return Redirect::route('index');
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        //        Event::fire('limits.check');
        //        Event::fire('piggybanks.check');
        //        Event::fire('recurring.check');

        // count, maybe Firefly needs some introducing text to show:
        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Database\TransactionJournal $jrnls */
        $jrnls = App::make('FireflyIII\Database\TransactionJournal');

        /** @var \FireflyIII\Shared\Preferences\PreferencesInterface $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\PreferencesInterface');

        $count = $acct->countAssetAccounts();

        $start = Session::get('start');
        $end   = Session::get('end');


        // get the preference for the home accounts to show:
        $frontpage = $preferences->get('frontpageAccounts', []);
        if ($frontpage->data == []) {
            $accounts = $acct->getAssetAccounts();
        } else {
            $accounts = $acct->getByIds($frontpage->data);
        }

        $transactions = [];
        foreach ($accounts as $account) {
            $set = $jrnls->getInDateRangeAccount($account, 10, $start, $end);
            if (count($set) > 0) {
                $transactions[] = [$set, $account];
            }
        }

        // build the home screen:
        return View::make('index')->with('count', $count)->with('transactions', $transactions)->with('title', 'Firefly')->with('subTitle', 'What\'s playing?')
                   ->with('mainTitleIcon', 'fa-fire');
    }

    /**
     * @param $range
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rangeJump($range)
    {

        $valid = ['1D', '1W', '1M', '3M', '6M', '1Y',];

        /** @var \FireflyIII\Shared\Preferences\PreferencesInterface $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\PreferencesInterface');

        if (in_array($range, $valid)) {
            $preferences->set('viewRange', $range);
            Session::forget('range');
        }

        return Redirect::back();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionNext()
    {
        /** @var \FireflyIII\Shared\Toolkit\Navigation $navigation */
        $navigation = App::make('FireflyIII\Shared\Toolkit\Navigation');
        $navigation->next();

        return Redirect::back();
        //return Redirect::route('index');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionPrev()
    {
        /** @var \FireflyIII\Shared\Toolkit\Navigation $navigation */
        $navigation = App::make('FireflyIII\Shared\Toolkit\Navigation');
        $navigation->prev();

        return Redirect::back();
        //return Redirect::route('index');
    }
}