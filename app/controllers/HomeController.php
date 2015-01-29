<?php
use Carbon\Carbon;

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
        // count, maybe Firefly needs some introducing text to show:
        /** @var \FireflyIII\Database\Account\Account $acct */
        $acct = App::make('FireflyIII\Database\Account\Account');

        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $journalRepository */
        $journalRepository = App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');

        /** @var \FireflyIII\Shared\Preferences\PreferencesInterface $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\PreferencesInterface');

        $count = $acct->countAccountsByType(['Default account', 'Asset account']);

        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());


        // get the preference for the home accounts to show:
        $frontPage = $preferences->get('frontPageAccounts', []);
        if ($frontPage->data == []) {
            $accounts = $acct->getAccountsByType(['Default account', 'Asset account']);
        } else {
            $accounts = $acct->getByIds($frontPage->data);
        }

        $transactions = [];
        foreach ($accounts as $account) {
            $set = $journalRepository->getInDateRangeAccount($account, $start, $end, 10);
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
        if (isset($_SERVER['HTTP_REFERER']) && (!strpos($_SERVER['HTTP_REFERER'], Config::get('app.url')) === false)) {
            return Redirect::back();
        } else {
            return Redirect::intended();
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionNext()
    {
        Navigation::next();
        $strPosition = strpos($_SERVER['HTTP_REFERER'], Config::get('app.url'));
        Log::debug('HTTP_REFERER is: ' . $_SERVER['HTTP_REFERER']);
        Log::debug('App.url = ' . Config::get('app.url'));
        Log::debug('strpos() = ' . Steam::boolString($strPosition));

        if (isset($_SERVER['HTTP_REFERER']) && $strPosition === 0) {
            Log::debug('Redirect back');
            return Redirect::back();
        } else {
            Log::debug('Redirect intended');
            return Redirect::intended();
        }

    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionPrev()
    {
        Navigation::prev();
        $strPosition = strpos($_SERVER['HTTP_REFERER'], Config::get('app.url'));
        Log::debug('HTTP_REFERER is: ' . $_SERVER['HTTP_REFERER']);
        Log::debug('App.url = ' . Config::get('app.url'));
        Log::debug('strpos() = ' . Steam::boolString($strPosition));


        if (isset($_SERVER['HTTP_REFERER']) && $strPosition === 0) {
            Log::debug('Redirect back');
            return Redirect::back();
        } else {
            Log::debug('Redirect intended');
            return Redirect::intended();
        }
    }
}
