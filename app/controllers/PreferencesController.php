<?php

use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

class PreferencesController extends BaseController
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
        $accounts = $this->accounts->getDefault();

        // pref:
        $frontpage = $this->preferences->get('frontpageAccounts', []);
        return View::make('preferences.index')->with('accounts', $accounts)->with('frontpageAccounts', $frontpage);
    }

    public function postIndex()
    {
        $frontpageAccounts = [];
        foreach(Input::get('frontpageAccounts') as $id) {
            $frontpageAccounts[] = intval($id);
        }
        $this->preferences->set('frontpageAccounts',$frontpageAccounts);
        Session::flash('success', 'Preferences saved!');
        return Redirect::route('preferences');
    }

} 