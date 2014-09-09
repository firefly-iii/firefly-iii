<?php
use Carbon\Carbon;
use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Reminder\ReminderRepositoryInterface as RRI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class HomeController
 */
class HomeController extends BaseController
{
    protected $_accounts;
    protected $_preferences;
    protected $_journal;
    protected $_reminders;

    public function __construct(ARI $accounts, PHI $preferences, TJRI $journal, RRI $reminders)
    {
        $this->_accounts = $accounts;
        $this->_preferences = $preferences;
        $this->_journal = $journal;
        $this->_reminders = $reminders;
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

        // count, maybe we need some introducing text to show:
        $count = $this->_accounts->count();
        $start = Session::get('start');
        $end = Session::get('end');


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

        if (count($transactions) % 2 == 0) {
            $transactions = array_chunk($transactions, 2);
        } elseif (count($transactions) == 1) {
            $transactions = array_chunk($transactions, 3);
        } else {
            $transactions = array_chunk($transactions, 3);
        }

        // build the home screen:
        return View::make('index')->with('count', $count)->with('transactions', $transactions);
    }
}