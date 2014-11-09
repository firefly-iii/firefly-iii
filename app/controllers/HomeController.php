<?php
use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class HomeController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class HomeController extends BaseController
{
    protected $_accounts;
    protected $_preferences;
    protected $_journal;

    /**
     * @param ARI  $accounts
     * @param PHI  $preferences
     * @param TJRI $journal
     */
    public function __construct(ARI $accounts, PHI $preferences, TJRI $journal)
    {
        $this->_accounts    = $accounts;
        $this->_preferences = $preferences;
        $this->_journal     = $journal;
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
        Event::fire('limits.check');
        Event::fire('piggybanks.check');
        Event::fire('recurring.check');

        // count, maybe Firefly needs some introducing text to show:
        $count = $this->_accounts->count();
        $start = Session::get('start');
        $end   = Session::get('end');


        // get the preference for the home accounts to show:
        $frontpage = $this->_preferences->get('frontpageAccounts', []);
        if ($frontpage->data == []) {
            $accounts = $this->_accounts->getActiveDefault();
        } else {
            $accounts = $this->_accounts->getByIds($frontpage->data);
        }

        $transactions = [];
        foreach ($accounts as $account) {
            $set = $this->_journal->getByAccountInDateRange($account, 10, $start, $end);
            if (count($set) > 0) {
                $transactions[] = [$set, $account];
            }
        }

        // build the home screen:
        return View::make('index')->with('count', $count)->with('transactions', $transactions)->with('title', 'Firefly')
                   ->with('subTitle', 'What\'s playing?')->with('mainTitleIcon', 'fa-fire');
    }

    public function cleanup()
    {
        /** @var \FireflyIII\Database\TransactionJournal $jrnls */
        $jrnls = App::make('FireflyIII\Database\TransactionJournal');

        /** @var \FireflyIII\Database\Account $acct */
        $acct = \App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Database\AccountType $acctType */
        $acctType      = \App::make('FireflyIII\Database\AccountType');
        $rightAcctType = $acctType->findByWhat('revenue');

        $all = $jrnls->get();

        /** @var \TransactionJournal $entry */
        foreach ($all as $entry) {
            $wrongFromType = false;
            $wrongToType   = false;
            $transactions  = $entry->transactions;
            if (count($transactions) == 2) {
                switch ($entry->transactionType->type) {
                    case 'Deposit':
                        /** @var \Transaction $transaction */
                        foreach ($transactions as $transaction) {
                            if (floatval($transaction->amount) < 0) {
                                $accountType = $transaction->account->accountType;
                                if ($accountType->type == 'Beneficiary account') {
                                    // should be a Revenue account!
                                    $name = $transaction->account->name;
                                    /** @var \Account $account */
                                    $account = \Auth::user()->accounts()->where('name', $name)->where('account_type_id', $rightAcctType->id)->first();
                                    if (!$account) {
                                        $new     = [
                                            'name' => $name,
                                            'what' => 'revenue'
                                        ];
                                        $account = $acct->store($new);
                                    }
                                    $transaction->account()->associate($account);
                                    $transaction->save();
                                }

                                echo 'Paid by: ' . $transaction->account->name . ' (' . $transaction->account->accountType->type . ')<br />';
                            }
                        }
                        break;
                }


            }
        }


    }
}