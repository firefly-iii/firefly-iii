<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Config;
use FireflyIII\Http\Requests\TokenFormRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use Input;
use PragmaRX\Google2FA\Contracts\Google2FA;
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
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.preferences'));
        View::share('mainTitleIcon', 'fa-gear');
    }

    /**
     * @param Google2FA $google2fa
     *
     * @return View
     */
    public function code(Google2FA $google2fa)
    {
        $domain = $this->getDomain();
        $secret = $google2fa->generateSecretKey(16, Auth::user()->id);
        Session::flash('two-factor-secret', $secret);
        $image = $google2fa->getQRCodeInline('Firefly III at ' . $domain, null, $secret, 150);


        return view('preferences.code', compact('image'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function deleteCode()
    {
        Preferences::delete('twoFactorAuthEnabled');
        Preferences::delete('twoFactorAuthSecret');
        Session::flash('success', strval(trans('firefly.pref_two_factor_auth_disabled')));
        Session::flash('info', strval(trans('firefly.pref_two_factor_auth_remove_it')));

        return redirect(route('preferences'));
    }

    /**
     * @param ARI $repository
     *
     * @return $this|\Illuminate\View\View
     */
    public function index(ARI $repository)
    {
        $accounts            = $repository->getAccounts(['Default account', 'Asset account']);
        $viewRangePref       = Preferences::get('viewRange', '1M');
        $viewRange           = $viewRangePref->data;
        $frontPageAccounts   = Preferences::get('frontPageAccounts', []);
        $budgetMax           = Preferences::get('budgetMaximum', 1000);
        $language            = Preferences::get('language', env('DEFAULT_LANGUAGE', 'en_US'))->data;
        $budgetMaximum       = $budgetMax->data;
        $transactionPageSize = Preferences::get('transactionPageSize', 50)->data;
        $customFiscalYear    = Preferences::get('customFiscalYear', 0)->data;
        $fiscalYearStartStr  = Preferences::get('fiscalYearStart', '01-01')->data;
        $fiscalYearStart     = date('Y') . '-' . $fiscalYearStartStr;
        $is2faEnabled        = Preferences::get('twoFactorAuthEnabled', 0)->data; // twoFactorAuthEnabled
        $has2faSecret        = !is_null(Preferences::get('twoFactorAuthSecret')); // hasTwoFactorAuthSecret
        $showIncomplete      = env('SHOW_INCOMPLETE_TRANSLATIONS', false) === true;

        return view(
            'preferences.index',
            compact(
                'budgetMaximum', 'language', 'accounts', 'frontPageAccounts',
                'viewRange', 'customFiscalYear', 'transactionPageSize', 'fiscalYearStart', 'is2faEnabled',
                'has2faSecret', 'showIncomplete'
            )
        );
    }

    /**
     * @param TokenFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postCode(TokenFormRequest $request)
    {
        Preferences::set('twoFactorAuthEnabled', 1);
        Preferences::set('twoFactorAuthSecret', Session::get('two-factor-secret'));

        Session::flash('success', strval(trans('firefly.saved_preferences')));
        Preferences::mark();

        return redirect(route('preferences'));
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

        // custom fiscal year
        $customFiscalYear = (int)Input::get('customFiscalYear');
        $fiscalYearStart  = date('m-d', strtotime(Input::get('fiscalYearStart')));
        Preferences::set('customFiscalYear', $customFiscalYear);
        Preferences::set('fiscalYearStart', $fiscalYearStart);

        // save page size:
        $transactionPageSize = intval(Input::get('transactionPageSize'));
        if ($transactionPageSize > 0 && $transactionPageSize < 1337) {
            Preferences::set('transactionPageSize', $transactionPageSize);
        } else {
            Preferences::set('transactionPageSize', 50);
        }

        // two factor auth
        $twoFactorAuthEnabled   = intval(Input::get('twoFactorAuthEnabled'));
        $hasTwoFactorAuthSecret = !is_null(Preferences::get('twoFactorAuthSecret'));

        // If we already have a secret, just set the two factor auth enabled to 1, and let the user continue with the existing secret.
        if ($hasTwoFactorAuthSecret) {
            Preferences::set('twoFactorAuthEnabled', $twoFactorAuthEnabled);
        }

        // language:
        $lang = Input::get('language');
        if (in_array($lang, array_keys(Config::get('firefly.languages')))) {
            Preferences::set('language', $lang);
        }


        Session::flash('success', strval(trans('firefly.saved_preferences')));
        Preferences::mark();

        // if we don't have a valid secret yet, redirect to the code page.
        // AND USER HAS ACTUALLY ENABLED 2FA
        if (!$hasTwoFactorAuthSecret && $twoFactorAuthEnabled === 1) {
            return redirect(route('preferences.code'));
        }

        return redirect(route('preferences'));
    }

    /**
     * @return string
     */
    private function getDomain() : string
    {
        $url   = url()->to('/');
        $parts = parse_url($url);

        return $parts['host'];
    }

}
