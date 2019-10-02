<?php
/**
 * TwoFactorController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
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
