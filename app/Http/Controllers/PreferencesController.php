<?php namespace FireflyIII\Http\Controllers;

use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Input;
use Preferences;
use Redirect;
use Session;
use View;

/**
 * Class PreferencesController
 *
 * @package FireflyIII\Http\Controllers
 */
class PreferencesController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', 'Preferences');
        View::share('mainTitleIcon', 'fa-gear');
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return $this|\Illuminate\View\View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $accounts          = $repository->getAccounts(['Default account', 'Asset account']);
        $viewRangePref     = Preferences::get('viewRange', '1M');
        $viewRange         = $viewRangePref->data;
        $frontPageAccounts = Preferences::get('frontPageAccounts', []);
        $budgetMax         = Preferences::get('budgetMaximum', 1000);
        $budgetMaximum     = $budgetMax->data;

        return view('preferences.index', compact('budgetMaximum', 'accounts', 'frontPageAccounts', 'viewRange'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postIndex()
    {
        // front page accounts
        $frontPageAccounts = [];
        foreach (Input::get('frontPageAccounts') as $id) {
            $frontPageAccounts[] = intval($id);
        }
        Preferences::set('frontPageAccounts', $frontPageAccounts);

        // view range:
        Preferences::set('viewRange', Input::get('viewRange'));
        // forget session values:
        Session::forget('start');
        Session::forget('end');
        Session::forget('range');

        // budget maximum:
        $budgetMaximum = intval(Input::get('budgetMaximum'));
        Preferences::set('budgetMaximum', $budgetMaximum);


        Session::flash('success', 'Preferences saved!');

        return Redirect::route('preferences');
    }

}
