<?php

/**
 * Class PreferencesController
 *
 */
class PreferencesController extends BaseController
{
    /**
     *
     */
    public function __construct()
    {

        View::share('title', 'Preferences');
        View::share('mainTitleIcon', 'fa-gear');
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');

        $accounts       = $acct->getAssetAccounts();
        $viewRange      = $preferences->get('viewRange', '1M');
        $viewRangeValue = $viewRange->data;
        $frontpage      = $preferences->get('frontpageAccounts', []);

        return View::make('preferences.index')->with('accounts', $accounts)->with('frontpageAccounts', $frontpage)->with('viewRange', $viewRangeValue);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postIndex()
    {

        /** @var \FireflyIII\Shared\Preferences\Preferences $preferences */
        $preferences = App::make('FireflyIII\Shared\Preferences\Preferences');

        // frontpage accounts
        $frontpageAccounts = [];
        foreach (Input::get('frontpageAccounts') as $id) {
            $frontpageAccounts[] = intval($id);
        }
        $preferences->set('frontpageAccounts', $frontpageAccounts);

        // view range:
        $preferences->set('viewRange', Input::get('viewRange'));
        // forget session values:
        Session::forget('start');
        Session::forget('end');
        Session::forget('range');

        Session::flash('success', 'Preferences saved!');

        return Redirect::route('preferences');
    }

} 