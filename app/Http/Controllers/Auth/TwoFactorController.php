<?php
/**
 * TwoFactorController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\TokenFormRequest;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Log;
use Preferences;

/**
 * Class TwoFactorController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class TwoFactorController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     * @throws FireflyException
     */
    public function index(Request $request)
    {

        $user = auth()->user();

        // to make sure the validator in the next step gets the secret, we push it in session
        $secretPreference = Preferences::get('twoFactorAuthSecret', null);
        $secret           = is_null($secretPreference) ? null : $secretPreference->data;
        $title            = strval(trans('firefly.two_factor_title'));

        // make sure the user has two factor configured:
        $has2FA = Preferences::get('twoFactorAuthEnabled', false)->data;
        if (is_null($has2FA) || $has2FA === false) {
            return redirect(route('index'));
        }

        if (strlen(strval($secret)) === 0) {
            throw new FireflyException('Your two factor authentication secret is empty, which it cannot be at this point. Please check the log files.');
        }
        $request->session()->flash('two-factor-secret', $secret);

        return view('auth.two-factor', compact('user', 'title'));
    }

    /**
     * @return mixed
     * @throws FireflyException
     */
    public function lostTwoFactor()
    {
        $user      = auth()->user();
        $siteOwner = env('SITE_OWNER', '');
        $title     = strval(trans('firefly.two_factor_forgot_title'));

        Log::info(
            'To reset the two factor authentication for user #' . $user->id .
            ' (' . $user->email . '), simply open the "preferences" table and delete the entries with the names "twoFactorAuthEnabled" and' .
            ' "twoFactorAuthSecret" for user_id ' . $user->id . '. That will take care of it.'
        );

        return view('auth.lost-two-factor', compact('user', 'siteOwner', 'title'));
    }

    /**
     * @param TokenFormRequest $request
     * @param CookieJar        $cookieJar
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) // it's unused but the class does some validation.
     *
     */
    public function postIndex(TokenFormRequest $request, CookieJar $cookieJar)
    {
        // set cookie!
        $cookie = $cookieJar->forever('twoFactorAuthenticated', 'true');

        return redirect(route('home'))->withCookie($cookie);
    }

}
