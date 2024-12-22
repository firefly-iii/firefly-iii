<?php

/**
 * TwoFactorController.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use FireflyIII\Events\Security\MFABackupFewLeft;
use FireflyIII\Events\Security\MFABackupNoLeft;
use FireflyIII\Events\Security\MFAManyFailedAttempts;
use FireflyIII\Events\Security\MFAUsedBackupCode;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FALaravel\Support\Authenticator;

/**
 * Class TwoFactorController.
 */
class TwoFactorController extends Controller
{
    /**
     * What to do if 2FA lost?
     *
     * @return Factory|View
     */
    public function lostTwoFactor()
    {
        /** @var User $user */
        $user      = auth()->user();
        $siteOwner = config('firefly.site_owner');
        $title     = (string) trans('firefly.two_factor_forgot_title');

        return view('auth.lost-two-factor', compact('user', 'siteOwner', 'title'));
    }

    /**
     * @return Redirector|RedirectResponse
     */
    public function submitMFA(Request $request)
    {
        /** @var array $mfaHistory */
        $mfaHistory    = app('preferences')->get('mfa_history', [])->data;
        $mfaCode       = (string) $request->get('one_time_password');

        // is in history? then refuse to use it.
        if ($this->inMFAHistory($mfaCode, $mfaHistory)) {
            $this->filterMFAHistory();
            session()->flash('error', trans('firefly.wrong_mfa_code'));

            return redirect(route('home'));
        }

        /** @var Authenticator $authenticator */
        $authenticator = app(Authenticator::class)->boot($request);

        // if not OK, save error.
        if (!$authenticator->isAuthenticated()) {
            $user    = auth()->user();
            $this->addToMFAFailureCounter();
            $counter = $this->getMFAFailureCounter();
            if (3 === $counter || 10 === $counter) {
                // do not reset MFA failure counter, but DO send a warning to the user.
                Log::channel('audit')->info(sprintf('User "%s" has had %d failed MFA attempts.', $user->email, $counter));
                event(new MFAManyFailedAttempts($user, $counter));
            }
            unset($user);
        }

        if ($authenticator->isAuthenticated()) {
            // save MFA in preferences
            $this->addToMFAHistory($mfaCode);

            // reset failure count
            $this->resetMFAFailureCounter();

            // otp auth success!
            return redirect(route('home'));
        }

        // could be user has a backup code.
        if ($this->isBackupCode($mfaCode)) {
            $this->removeFromBackupCodes($mfaCode);
            $authenticator->login();

            // reset failure count
            $this->resetMFAFailureCounter();

            session()->flash('info', trans('firefly.mfa_backup_code'));
            // send user notification.
            $user = auth()->user();
            Log::channel('audit')->info(sprintf('User "%s" has used a backup code.', $user->email));
            event(new MFAUsedBackupCode($user));

            return redirect(route('home'));
        }

        session()->flash('error', trans('firefly.wrong_mfa_code'));

        return redirect(route('home'));
    }

    /**
     * Each MFA history has a timestamp and a code, saving the MFA entries for 5 minutes. So if the
     * submitted MFA code has been submitted in the last 5 minutes, it won't work despite being valid.
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
     * Remove old entries from the preferences array.
     */
    private function filterMFAHistory(): void
    {
        /** @var array $mfaHistory */
        $mfaHistory = app('preferences')->get('mfa_history', [])->data;
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
        app('preferences')->set('mfa_history', $newHistory);
    }

    private function addToMFAFailureCounter(): void
    {
        $preference = (int) app('preferences')->get('mfa_failure_count', 0)->data;
        ++$preference;
        Log::channel('audit')->info(sprintf('MFA failure count is set to %d.', $preference));
        app('preferences')->set('mfa_failure_count', $preference);
    }

    private function getMFAFailureCounter(): int
    {
        $value = (int) app('preferences')->get('mfa_failure_count', 0)->data;
        Log::channel('audit')->info(sprintf('MFA failure count is %d.', $value));

        return $value;
    }

    private function addToMFAHistory(string $mfaCode): void
    {
        /** @var array $mfaHistory */
        $mfaHistory   = app('preferences')->get('mfa_history', [])->data;
        $entry        = [
            'time' => time(),
            'code' => $mfaCode,
        ];
        $mfaHistory[] = $entry;

        app('preferences')->set('mfa_history', $mfaHistory);
        $this->filterMFAHistory();
    }

    private function resetMFAFailureCounter(): void
    {
        app('preferences')->set('mfa_failure_count', 0);
        Log::channel('audit')->info('MFA failure count is set to zero.');
    }

    /**
     * Checks if code is in users backup codes.
     */
    private function isBackupCode(string $mfaCode): bool
    {
        $list = app('preferences')->get('mfa_recovery', [])->data;
        if (!is_array($list)) {
            $list = [];
        }
        if (in_array($mfaCode, $list, true)) {
            return true;
        }

        return false;
    }

    /**
     * Remove the used code from the list of backup codes.
     */
    private function removeFromBackupCodes(string $mfaCode): void
    {
        $list    = app('preferences')->get('mfa_recovery', [])->data;
        if (!is_array($list)) {
            $list = [];
        }
        $newList = array_values(array_diff($list, [$mfaCode]));

        // if the list is 3 or less, send a notification.
        if (count($newList) <= 3 && count($newList) > 0) {
            $user = auth()->user();
            Log::channel('audit')->info(sprintf('User "%s" has used a backup code. They have %d backup codes left.', $user->email, count($newList)));
            event(new MFABackupFewLeft($user, count($newList)));
        }
        // if the list is empty, send notification
        if (0 === count($newList)) {
            $user = auth()->user();
            Log::channel('audit')->info(sprintf('User "%s" has used their last backup code.', $user->email));
            event(new MFABackupNoLeft($user));
        }

        app('preferences')->set('mfa_recovery', $newList);
    }
}
