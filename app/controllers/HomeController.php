<?php
use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Helper\Toolkit\ToolkitInterface as Toolkit;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class HomeController
 */
class HomeController extends BaseController
{
    protected $_accounts;
    protected $_preferences;
    protected $_journal;
    protected $_budgets;
    protected $_tk;

    /**
     * @param ARI $accounts
     * @param PHI $preferences
     * @param TJRI $journal
     * @param Toolkit $toolkit
     * @param BRI $budgets
     */
    public function __construct(ARI $accounts, PHI $preferences, TJRI $journal, Toolkit $toolkit, BRI $budgets)
    {
        $this->_accounts = $accounts;
        $this->_preferences = $preferences;
        $this->_journal = $journal;
        $this->_tk = $toolkit;
        $this->_budgets = $budgets;
        View::share('menu', 'home');


    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        // count, maybe we need some introducing text to show:
        $count = $this->_accounts->count();
        list($start, $end) = $this->_tk->getDateRangeDates();


        // get the preference for the home accounts to show:
        $frontpage = $this->_preferences->get('frontpageAccounts', []);
        if ($frontpage->data == []) {
            $accounts = $this->_accounts->getActiveDefault();
        } else {
            $accounts = $this->_accounts->getByIds($frontpage->data);
        }


        // get the budgets for this period:
        $budgets = $this->_budgets->getWithRepetitionsInPeriod($start, \Session::get('range'));

        $transactions = [];
        foreach ($accounts as $account) {
            $set = $this->_journal->getByAccountInDateRange($account, 15, $start, $end);
            if (count($set) > 0) {
                $transactions[] = [$set, $account];
            }
        }

        if (count($transactions) % 2 == 0) {
            $transactions = array_chunk($transactions, 2);
        } elseif (count($transactions) == 1) {
            $transactions = array_chunk($transactions, 3);
        } else {
            $transactions = array_chunk($transactions, 3);
        }
        // build the home screen:
        return View::make('index')->with('count', $count)->with('transactions', $transactions)->with(
            'budgets', $budgets
        );
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function flush()
    {
        Cache::flush();
        return Redirect::route('index');
    }
}