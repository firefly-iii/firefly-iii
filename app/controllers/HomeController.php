<?php
use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;


class HomeController extends BaseController
{
    protected $accounts;
    protected $preferences;
    protected $tj;

    public function __construct(ARI $accounts, PHI $preferences, TJRI $tj)
    {
        $this->accounts = $accounts;
        $this->preferences = $preferences;
        $this->tj = $tj;
        View::share('menu', 'home');
    }

    public function index()
    {
        // get preferred viewing range
        $viewRange = $this->preferences->get('viewRange','week');


        // get list setting:
        $pref = $this->preferences->get('frontpageAccounts', []);

        // get the accounts to display on the home screen:
        $count = $this->accounts->count();
        if ($pref->data == []) {
            $list = $this->accounts->getActiveDefault();
        } else {
            $list = $this->accounts->getByIds($pref->data);
        }

        // get transactions for each account:
        foreach ($list as $account) {
            $account->transactionList = $this->tj->getByAccount($account,10);
        }


        // build the home screen:
        return View::make('index')->with('count', $count)->with('accounts', $list);
    }
}