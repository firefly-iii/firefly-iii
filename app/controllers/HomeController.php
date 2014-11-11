<?php
use FireflyIII\Shared\Preferences\PreferencesInterface as Prefs;

/**
 * Class HomeController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class HomeController extends BaseController
{
    protected $_preferences;
    protected $_journal;

    /**
     * @param PHI  $preferences
     */
    public function __construct(Prefs $preferences)
    {
        $this->_preferences = $preferences;
    }

    public function jobDev()
    {
        $fullName = storage_path() . DIRECTORY_SEPARATOR . 'firefly-export-2014-07-23.json';
        \Log::notice('Pushed start job.');
        Queue::push('Firefly\Queue\Import@start', ['file' => $fullName, 'user' => 1]);

    }

    /*
     *
     */
    public function sessionPrev()
    {
        /** @var \Firefly\Helper\Toolkit\ToolkitInterface $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->prev();
        return Redirect::back();
        //return Redirect::route('index');
    }

    /*
     *
     */
    public function sessionNext()
    {
        /** @var \Firefly\Helper\Toolkit\ToolkitInterface $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->next();
        return Redirect::back();
        //return Redirect::route('index');
    }

    public function rangeJump($range)
    {

        $valid = ['1D', '1W', '1M', '3M', '6M', '1Y',];

        if (in_array($range, $valid)) {
            $this->_preferences->set('viewRange', $range);
            Session::forget('range');
        }
        return Redirect::back();
    }

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

        $count = $acct->countAssetAccounts();

        $start = Session::get('start');
        $end   = Session::get('end');


        // get the preference for the home accounts to show:
        $frontpage = $this->_preferences->get('frontpageAccounts', []);
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
        return View::make('index')->with('count', $count)->with('transactions', $transactions)->with('title', 'Firefly')
                   ->with('subTitle', 'What\'s playing?')->with('mainTitleIcon', 'fa-fire');
    }
}