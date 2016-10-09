<?php
/**
 * PreferencesController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Http\Controllers;

use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Http\Requests\TokenFormRequest;
use FireflyIII\Models\AccountType;
use Illuminate\Http\Request;
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
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $secret = $google2fa->generateSecretKey(16, auth()->user()->id);
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
     * @param AccountCrudInterface $crud
     *
     * @return View
     */
    public function index(AccountCrudInterface $crud)
    {
        $accounts            = $crud->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $viewRangePref       = Preferences::get('viewRange', '1M');
        $viewRange           = $viewRangePref->data;
        $frontPageAccounts   = Preferences::get('frontPageAccounts', []);
        $language            = Preferences::get('language', config('firefly.default_language', 'en_US'))->data;
        $transactionPageSize = Preferences::get('transactionPageSize', 50)->data;
        $customFiscalYear    = Preferences::get('customFiscalYear', 0)->data;
        $fiscalYearStartStr  = Preferences::get('fiscalYearStart', '01-01')->data;
        $fiscalYearStart     = date('Y') . '-' . $fiscalYearStartStr;
        $tjOptionalFields    = Preferences::get('transaction_journal_optional_fields', [])->data;
        $is2faEnabled        = Preferences::get('twoFactorAuthEnabled', 0)->data; // twoFactorAuthEnabled
        $has2faSecret        = !is_null(Preferences::get('twoFactorAuthSecret')); // hasTwoFactorAuthSecret
        $showIncomplete      = env('SHOW_INCOMPLETE_TRANSLATIONS', false) === true;

        return view(
            'preferences.index',
            compact(
                'language', 'accounts', 'frontPageAccounts', 'tjOptionalFields',
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
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postIndex(Request $request)
    {
        // front page accounts
        $frontPageAccounts = [];
        if (is_array($request->get('frontPageAccounts'))) {
            foreach ($request->get('frontPageAccounts') as $id) {
                $frontPageAccounts[] = intval($id);
            }
            Preferences::set('frontPageAccounts', $frontPageAccounts);
        }

        // view range:
        Preferences::set('viewRange', $request->get('viewRange'));
        // forget session values:
        Session::forget('start');
        Session::forget('end');
        Session::forget('range');

        // custom fiscal year
        $customFiscalYear = intval($request->get('customFiscalYear')) === 1;
        $fiscalYearStart  = date('m-d', strtotime($request->get('fiscalYearStart')));
        Preferences::set('customFiscalYear', $customFiscalYear);
        Preferences::set('fiscalYearStart', $fiscalYearStart);

        // save page size:
        $transactionPageSize = intval($request->get('transactionPageSize'));
        if ($transactionPageSize > 0 && $transactionPageSize < 1337) {
            Preferences::set('transactionPageSize', $transactionPageSize);
        } else {
            Preferences::set('transactionPageSize', 50);
        }

        // two factor auth
        $twoFactorAuthEnabled   = intval($request->get('twoFactorAuthEnabled'));
        $hasTwoFactorAuthSecret = !is_null(Preferences::get('twoFactorAuthSecret'));

        // If we already have a secret, just set the two factor auth enabled to 1, and let the user continue with the existing secret.
        if ($hasTwoFactorAuthSecret) {
            Preferences::set('twoFactorAuthEnabled', $twoFactorAuthEnabled);
        }

        // language:
        $lang = $request->get('language');
        if (in_array($lang, array_keys(config('firefly.languages')))) {
            Preferences::set('language', $lang);
        }

        // optional fields for transactions:
        $setOptions = $request->get('tj');
        $optionalTj = [
            'interest_date'      => isset($setOptions['interest_date']),
            'book_date'          => isset($setOptions['book_date']),
            'process_date'       => isset($setOptions['process_date']),
            'due_date'           => isset($setOptions['due_date']),
            'payment_date'       => isset($setOptions['payment_date']),
            'invoice_date'       => isset($setOptions['invoice_date']),
            'internal_reference' => isset($setOptions['internal_reference']),
            'notes'              => isset($setOptions['notes']),
            'attachments'        => isset($setOptions['attachments']),
        ];
        Preferences::set('transaction_journal_optional_fields', $optionalTj);


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
