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
        // get the accounts to display on the home screen:
        $count = $this->_accounts->count();

        // build the home screen:
        return View::make('index')->with('count', $count);
    }
}