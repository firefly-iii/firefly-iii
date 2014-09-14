<?php
use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Reminder\ReminderRepositoryInterface as RRI;
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
    protected $_reminders;

    /**
     * @param ARI  $accounts
     * @param PHI  $preferences
     * @param TJRI $journal
     * @param RRI  $reminders
     */
    public function __construct(ARI $accounts, PHI $preferences, TJRI $journal, RRI $reminders)
    {
        $this->_accounts    = $accounts;
        $this->_preferences = $preferences;
        $this->_journal     = $journal;
        $this->_reminders   = $reminders;
    }

    public function jobDev() {
        $fullName = storage_path().DIRECTORY_SEPARATOR.'firefly-export-2014-07-23.json';
        \Log::notice('Pushed start job.');
        Queue::push('Firefly\Queue\Import@start', ['file' => $fullName, 'user' => 1]);

    }

    /*
     *
     */
    public function sessionPrev() {
        /** @var \Firefly\Helper\Toolkit\ToolkitInterface $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->prev();
        return Redirect::route('index');
    }

    /*
     *
     */
    public function sessionNext() {
        /** @var \Firefly\Helper\Toolkit\ToolkitInterface $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\ToolkitInterface');
        $toolkit->next();
        return Redirect::route('index');
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
            ->with('subTitle', 'What\'s playing?');
    }
}