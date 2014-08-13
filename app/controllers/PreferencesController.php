<?php

use Firefly\Helper\Preferences\PreferencesHelperInterface as PHI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

/**
 * Class PreferencesController
 */
class PreferencesController extends BaseController
{
    protected $_accounts;
    protected $_preferences;

    /**
     * @param ARI $accounts
     * @param PHI $preferences
     */
    public function __construct(ARI $accounts, PHI $preferences)
    {

        $this->_accounts = $accounts;
        $this->_preferences = $preferences;
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        $accounts = $this->_accounts->getDefault();

        $viewRange = $this->_preferences->get('viewRange', '1M');
        $viewRangeValue = $viewRange->data;

        // pref:
        $frontpage = $this->_preferences->get('frontpageAccounts', []);

        return View::make('preferences.index')->with('accounts', $accounts)->with('frontpageAccounts', $frontpage)
            ->with('viewRange', $viewRangeValue);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postIndex()
    {

        // frontpage accounts
        $frontpageAccounts = [];
        foreach (Input::get('frontpageAccounts') as $id) {
            $frontpageAccounts[] = intval($id);
        }
        $this->_preferences->set('frontpageAccounts', $frontpageAccounts);

        // view range:
        $this->_preferences->set('viewRange', Input::get('viewRange'));
        // forget session values:
        Session::forget('start');
        Session::forget('end');
        Session::forget('range');

        Session::flash('success', 'Preferences saved!');

        return Redirect::route('preferences');
    }

} 