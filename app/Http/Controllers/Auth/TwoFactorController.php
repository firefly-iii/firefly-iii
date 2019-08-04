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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\TokenFormRequest;
use FireflyIII\User;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Log;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use Preferences;

/**
 * Class TwoFactorController.
 */
class TwoFactorController extends Controller
{
    /**
     * @param Request $request
     */
    public function submitMFA(Request $request)
    {
        /** @var array $mfaHistory */
        $mfaHistory = Preferences::get('mfa_history', [])->data;
        $mfaCode    = $request->get('one_time_password');

        // is in history? then refuse to use it.
        if ($this->inMFAHistory($mfaCode, $mfaHistory)) {
            $this->filterMFAHistory();
            session()->flash('error', trans('firefly.wrong_mfa_code'));

            return redirect(route('home'));
        }

        /** @var Authenticator $authenticator */
        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated()) {
            // save MFA in preferences
            $this->addToMFAHistory($mfaCode);

            // otp auth success!
            return redirect(route('home'));
        }

        // could be user has a backup code.
        if ($this->isBackupCode($mfaCode)) {
            $this->removeFromBackupCodes($mfaCode);
            $authenticator->login();

            session()->flash('info', trans('firefly.mfa_backup_code'));

            return redirect(route('home'));
        }

        session()->flash('error', trans('firefly.wrong_mfa_code'));

        return redirect(route('home'));
    }

    /**
     * @param string $mfaCode
     */
    private function addToMFAHistory(string $mfaCode): void
    {
        /** @var array $mfaHistory */
        $mfaHistory   = Preferences::get('mfa_history', [])->data;
        $entry        = [
            'time' => time(),
            'code' => $mfaCode,
        ];
        $mfaHistory[] = $entry;

        Preferences::set('mfa_history', $mfaHistory);
        $this->filterMFAHistory();
    }

    /**
     * Remove old entries from the preferences array.
     */
    private function filterMFAHistory(): void
    {
        /** @var array $mfaHistory */
        $mfaHistory = Preferences::get('mfa_history', [])->data;
        $newHistory = [];
        $now        = time();
        foreach ($mfaHistory as $entry) {
            $time = $entry['time'];
            $code = $entry['code'];
            if ($now - $time <= 300) {
                $newHistory[] = [
                    'time' => $time,
                    'code' => $code,
                ];
            }
        }
        Preferences::set('mfa_history', $newHistory);
    }

    /**
     * Show 2FA screen.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     *
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function index(Request $request)
    {
        die('this auth controller must be refactored.');
        $user = auth()->user();

        // to make sure the validator in the next step gets the secret, we push it in session
        $secretPreference = app('preferences')->get('twoFactorAuthSecret', null);
        $secret           = null === $secretPreference ? null : $secretPreference->data;
        $title            = (string)trans('firefly.two_factor_title');

        // make sure the user has two factor configured:
        $has2FA = app('preferences')->get('twoFactorAuthEnabled', false)->data;
        if (null === $has2FA || false === $has2FA) {
            return redirect(route('index'));
        }

        if ('' === (string)$secret) {
            throw new FireflyException('Your two factor authentication secret is empty, which it cannot be at this point. Please check the log files.');
        }
        $request->session()->flash('two-factor-secret', $secret);

        return view('auth.two-factor', compact('user', 'title'));
    }

    /**
     * What to do if 2FA lost?
     *
     * @return mixed
     */
    public function lostTwoFactor()
    {
        /** @var User $user */
        $user      = auth()->user();
        $siteOwner = config('firefly.site_owner');
        $title     = (string)trans('firefly.two_factor_forgot_title');

        Log::info(
            'To reset the two factor authentication for user #' . $user->id .
            ' (' . $user->email . '), simply open the "preferences" table and delete the entries with the names "twoFactorAuthEnabled" and' .
            ' "twoFactorAuthSecret" for user_id ' . $user->id . '. That will take care of it.'
        );

        return view('auth.lost-two-factor', compact('user', 'siteOwner', 'title'));
    }

    /**
     * Submit 2FA code.
     *
     * @param TokenFormRequest $request
     * @param CookieJar        $cookieJar
     *
     * @return mixed
     */
    public function postIndex(TokenFormRequest $request, CookieJar $cookieJar)
    {
        // wants to remember session?
        $remember = $request->session()->get('remember_login') ?? false;

        $minutes = config('session.lifetime');
        if (true === $remember) {
            // set cookie with a long lifetime (30 days)
            $minutes = 43200;
        }
        $cookie = $cookieJar->make(
            'twoFactorAuthenticated', 'true', $minutes, config('session.path'), config('session.domain'), config('session.secure'), config('session.http_only')
        );

        // whatever the case, forget about it:
        $request->session()->forget('remember_login');

        return redirect(route('home'))->withCookie($cookie);
    }

    /**
     * Each MFA history has a timestamp and a code, saving the MFA entries for 5 minutes. So if the
     * submitted MFA code has been submitted in the last 5 minutes, it won't work despite being valid.
     *
     * @param string $mfaCode
     * @param array  $mfaHistory
     *
     * @return bool
     */
    private function inMFAHistory(string $mfaCode, array $mfaHistory): bool
    {
        $now = time();
        foreach ($mfaHistory as $entry) {
            $time = $entry['time'];
            $code = $entry['code'];
            if ($code === $mfaCode && $now - $time <= 300) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if code is in users backup codes.
     *
     * @param string $mfaCode
     *
     * @return bool
     */
    private function isBackupCode(string $mfaCode): bool
    {
        $list = Preferences::get('mfa_recovery', [])->data;
        if (in_array($mfaCode, $list, true)) {
            return true;
        }

        return false;
    }

    /**
     * Remove the used code from the list of backup codes.
     *
     * @param string $mfaCode
     */
    private function removeFromBackupCodes(string $mfaCode): void
    {
        $list    = Preferences::get('mfa_recovery', [])->data;
        $newList = array_values(array_diff($list, [$mfaCode]));
        Preferences::set('mfa_recovery', $newList);
    }
}
