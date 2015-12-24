<?php namespace FireflyIII\Http\Controllers;

use Config;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Input;
use Preferences;
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
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.preferences'));
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
        $language          = Preferences::get('language', env('DEFAULT_LANGUAGE', 'en_US'))->data;
        $budgetMaximum     = $budgetMax->data;

        $showIncomplete = env('SHOW_INCOMPLETE_TRANSLATIONS', 'false') == 'true';

        return view('preferences.index', compact('budgetMaximum', 'language', 'accounts', 'frontPageAccounts', 'viewRange', 'showIncomplete'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postIndex()
    {
        // front page accounts
        $frontPageAccounts = [];
        if (is_array(Input::get('frontPageAccounts'))) {
            foreach (Input::get('frontPageAccounts') as $id) {
                $frontPageAccounts[] = intval($id);
            }
            Preferences::set('frontPageAccounts', $frontPageAccounts);
        }

        // view range:
        Preferences::set('viewRange', Input::get('viewRange'));
        // forget session values:
        Session::forget('start');
        Session::forget('end');
        Session::forget('range');

        // budget maximum:
        $budgetMaximum = intval(Input::get('budgetMaximum'));
        Preferences::set('budgetMaximum', $budgetMaximum);

        // language:
        $lang = Input::get('language');
        if (in_array($lang, array_keys(Config::get('firefly.languages')))) {
            Preferences::set('language', $lang);
        }


        Session::flash('success', 'Preferences saved!');
        Preferences::mark();

        return redirect(route('preferences'));
    }

}
