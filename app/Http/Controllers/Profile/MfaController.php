<?php

/*
 * MfaController.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Profile;

use FireflyIII\Events\Security\DisabledMFA;
use FireflyIII\Events\Security\EnabledMFA;
use FireflyIII\Events\Security\MFANewBackupCodes;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Requests\ExistingTokenFormRequest;
use FireflyIII\Http\Requests\TokenFormRequest;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use PragmaRX\Recovery\Recovery;

/**
 * Class MfaController
 *
 * Enable MFA Flow:
 *
 * Page 1 (GET): Show QR code and the manual code. Secret keeps rotating.
 *  POST: store secret, store response, validate password.
 * ---
 * Page 3 (GET): Confirm 2FA status and show recovery codes.
 *        Same page as page 1, but when secret is present.
 */
class MfaController extends Controller
{
    protected bool $internalAuth;

    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            static function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.profile'));
                app('view')->share('mainTitleIcon', 'fa-user');

                return $next($request);
            }
        );
        $authGuard          = config('firefly.authentication_guard');
        $this->internalAuth = 'web' === $authGuard;
        app('log')->debug(sprintf('ProfileController::__construct(). Authentication guard is "%s"', $authGuard));

        $this->middleware(IsDemoUser::class)->except(['index']);

    }

    /**
     * @throws FireflyException
     */
    public function backupCodes(Request $request): Factory|RedirectResponse|View
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }
        $enabledMFA = null !== auth()->user()->mfa_secret;
        if (false === $enabledMFA) {
            request()->session()->flash('info', trans('firefly.mfa_not_enabled'));

            return redirect(route('profile.index'));
        }

        return view('profile.mfa.backup-codes-intro');
    }

    public function backupCodesPost(ExistingTokenFormRequest $request): Redirector|RedirectResponse|View
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }
        $enabledMFA    = null !== auth()->user()->mfa_secret;
        if (false === $enabledMFA) {
            request()->session()->flash('info', trans('firefly.mfa_not_enabled'));

            return redirect(route('profile.index'));
        }
        // generate recovery codes:
        $recovery      = app(Recovery::class);
        $recoveryCodes = $recovery->lowercase()
            ->setCount(8)     // Generate 8 codes
            ->setBlocks(2)    // Every code must have 2 blocks
            ->setChars(6)     // Each block must have 6 chars
            ->toArray()
        ;
        $codes         = implode("\r\n", $recoveryCodes);

        app('preferences')->set('mfa_recovery', $recoveryCodes);
        app('preferences')->mark();

        // send user notification.
        $user          = auth()->user();
        Log::channel('audit')->info(sprintf('User "%s" has generated new backup codes.', $user->email));
        event(new MFANewBackupCodes($user));

        return view('profile.mfa.backup-codes-post')->with(compact('codes'));

    }

    public function disableMFA(Request $request): Factory|RedirectResponse|View
    {
        if (!$this->internalAuth) {
            request()->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }
        $enabledMFA   = null !== auth()->user()->mfa_secret;
        if (false === $enabledMFA) {
            request()->session()->flash('info', trans('firefly.mfa_already_disabled'));

            return redirect(route('profile.index'));
        }
        $subTitle     = (string) trans('firefly.mfa_index_title');
        $subTitleIcon = 'fa-calculator';

        return view('profile.mfa.disable-mfa')->with(compact('subTitle', 'subTitleIcon', 'enabledMFA'));
    }

    /**
     * Delete 2FA routine.
     */
    public function disableMFAPost(ExistingTokenFormRequest $request): Redirector|RedirectResponse
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        /** @var User $user */
        $user       = auth()->user();

        app('preferences')->delete('temp-mfa-secret');
        app('preferences')->delete('temp-mfa-codes');
        $repository->setMFACode($user, null);
        app('preferences')->mark();

        session()->flash('success', (string) trans('firefly.pref_two_factor_auth_disabled'));
        session()->flash('info', (string) trans('firefly.pref_two_factor_auth_remove_it'));

        // also logout current 2FA tokens.
        $cookieName = config('google2fa.cookie_name', 'google2fa_token');
        \Cookie::forget($cookieName);

        // send user notification.
        Log::channel('audit')->info(sprintf('User "%s" has disabled MFA', $user->email));
        event(new DisabledMFA($user));

        return redirect(route('profile.index'));
    }

    /**
     * Enable 2FA screen.
     */
    public function enableMFA(Request $request): Redirector|RedirectResponse|View
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        /** @var User $user */
        $user       = auth()->user();
        $enabledMFA = null !== $user->mfa_secret;

        // If FF3 already has a secret, just set the two-factor auth enabled to 1,
        // and let the user continue with the existing secret.
        if ($enabledMFA) {
            session()->flash('info', (string) trans('firefly.2fa_already_enabled'));

            return redirect(route('profile.index'));
        }

        $domain     = $this->getDomain();
        $secret     = \Google2FA::generateSecretKey();
        $image      = \Google2FA::getQRCodeInline($domain, auth()->user()->email, (string) $secret);

        app('preferences')->set('temp-mfa-secret', $secret);


        return view('profile.mfa.enable-mfa', compact('image', 'secret'));

    }

    /**
     * Submit 2FA for the first time.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function enableMFAPost(TokenFormRequest $request)
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        /** @var User $user */
        $user       = auth()->user();

        // verify password.
        $password   = $request->get('password');
        if (!auth()->validate(['email' => $user->email, 'password' => $password])) {
            session()->flash('error', 'Bad user pw, no MFA for you!');

            return redirect(route('profile.mfa.index'));
        }

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $secret     = app('preferences')->get('temp-mfa-secret')?->data;
        if (is_array($secret)) {
            $secret = null;
        }
        $secret     = (string) $secret;

        $repository->setMFACode($user, $secret);

        app('preferences')->delete('temp-mfa-secret');

        session()->flash('success', (string) trans('firefly.saved_preferences'));
        app('preferences')->mark();

        // also save the code so replay attack is prevented.
        $mfaCode    = $request->get('code');
        $this->addToMFAHistory($mfaCode);

        // make sure MFA is logged out.
        if ('testing' !== config('app.env')) {
            \Google2FA::logout();
        }

        // drop all info from session:
        session()->forget(['temp-mfa-secret', 'two-factor-secret', 'two-factor-codes']);

        // send user notification.
        Log::channel('audit')->info(sprintf('User "%s" has enabled MFA', $user->email));
        event(new EnabledMFA($user));

        return redirect(route('profile.mfa.backup-codes'));
    }

    /**
     * TODO duplicate code.
     *
     * @throws FireflyException
     */
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

    public function index(): Factory|RedirectResponse|View
    {
        if (!$this->internalAuth) {
            request()->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        $subTitle     = (string) trans('firefly.mfa_index_title');
        $subTitleIcon = 'fa-calculator';
        $enabledMFA   = null !== auth()->user()->mfa_secret;

        return view('profile.mfa.index')->with(compact('subTitle', 'subTitleIcon', 'enabledMFA'));
    }
}
