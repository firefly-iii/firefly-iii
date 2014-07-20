<?php
use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class HomeController
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
        $this->_accounts = $accounts;
        $this->_preferences = $preferences;
        $this->_journal = $journal;
        View::share('menu', 'home');



    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        // count, maybe we need some introductionary text to show:
        $count = $this->_accounts->count();


        // get the preference for the home accounts to show:
        $frontpage = $this->_preferences->get('frontpageAccounts', []);

        $accounts = $this->_accounts->getByIds($frontpage->data);

        $transactions = [];
        foreach($accounts as $account) {
            $transactions[] = [$this->_journal->getByAccount($account,15),$account];
        }

        if(count($transactions) % 2 == 0) {
            $transactions = array_chunk($transactions, 2);
        } elseif(count($transactions) == 1) {
            $transactions = array_chunk($transactions, 3);
        } else {
            $transactions = array_chunk($transactions, 3);
        }
        // build the home screen:
        return View::make('index')->with('count', $count)->with('transactions',$transactions);
    }
}