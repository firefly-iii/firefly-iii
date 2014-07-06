<?php
use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;


class HomeController extends BaseController
{
    protected $accounts;
    protected $preferences;

    public function __construct(ARI $accounts, PHI $preferences)
    {
        $this->accounts = $accounts;
        $this->preferences = $preferences;
        View::share('menu', 'home');
    }

    public function index()
    {
        // get list setting:
        $pref = $this->preferences->get('frontpageAccounts', []);

        // get the accounts to display on the home screen:
        $count = $this->accounts->count();
        if ($pref->data == []) {
            $list = $this->accounts->getActiveDefault();
        } else {
            $list = $this->accounts->getByIds($pref->data);
        }


        // build the home screen:
        return View::make('index')->with('count', $count)->with('accounts', $list);
    }
}