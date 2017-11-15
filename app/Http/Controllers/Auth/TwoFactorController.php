<?php
/**
 * TwoFactorController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
 * Class TwoFactorController.
 */
class TwoFactorController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // to make sure the validator in the next step gets the secret, we push it in session
        $secretPreference = Preferences::get('twoFactorAuthSecret', null);
        $secret           = null === $secretPreference ? null : $secretPreference->data;
        $title            = strval(trans('firefly.two_factor_title'));

        // make sure the user has two factor configured:
        $has2FA = Preferences::get('twoFactorAuthEnabled', false)->data;
        if (null === $has2FA || false === $has2FA) {
            return redirect(route('index'));
        }

        if (0 === strlen(strval($secret))) {
            throw new FireflyException('Your two factor authentication secret is empty, which it cannot be at this point. Please check the log files.');
        }
        $request->session()->flash('two-factor-secret', $secret);

        return view('auth.two-factor', compact('user', 'title'));
    }

    /**
     * @return mixed
     *
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
     */
    public function postIndex(TokenFormRequest $request, CookieJar $cookieJar)
    {
        // set cookie!
        $cookie = $cookieJar->forever('twoFactorAuthenticated', 'true');

        return redirect(route('home'))->withCookie($cookie);
    }
}
