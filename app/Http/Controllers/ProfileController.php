<?php

/**
 * ProfileController.php
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

namespace FireflyIII\Http\Controllers;

use Auth;
use DB;
use Exception;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\EmailFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\Http\Requests\TokenFormRequest;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use FireflyIII\User;
use Google2FA;
use Hash;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Passport\ClientRepository;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Recovery\Recovery;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ProfileController.
 *
 * @method Guard guard()
 *
 */
class ProfileController extends Controller
{
    use CreateStuff;

    protected bool $internalAuth;

    /**
     * ProfileController constructor.
     *

     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            static function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.profile'));
                app('view')->share('mainTitleIcon', 'fa-user');

                return $next($request);
            }
        );
        $authGuard          = config('firefly.authentication_guard');
        $this->internalAuth = 'web' === $authGuard;
        Log::debug(sprintf('ProfileController::__construct(). Authentication guard is "%s"', $authGuard));

        $this->middleware(IsDemoUser::class)->except(['index']);
    }

    /**
     * View that generates a 2FA code for the user.
     *
     * @param Request $request
     *
     * @return Factory|View|RedirectResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function code(Request $request): Factory | View | RedirectResponse
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }
        $domain           = $this->getDomain();
        $secretPreference = Preferences::get('temp-mfa-secret');
        $codesPreference  = Preferences::get('temp-mfa-codes');

        // generate secret if not in session
        if (null === $secretPreference) {
            // generate secret + store + flash
            $secret = Google2FA::generateSecretKey();
            Preferences::set('temp-mfa-secret', $secret);
        }

        // re-use secret if in session
        if (null !== $secretPreference) {
            // get secret from session and flash
            $secret = $secretPreference->data;
        }

        // generate recovery codes if not in session:
        $recoveryCodes = '';

        if (null === $codesPreference) {
            // generate codes + store + flash:
            $recovery      = app(Recovery::class);
            $recoveryCodes = $recovery->lowercase()->setCount(8)->setBlocks(2)->setChars(6)->toArray();
            Preferences::set('temp-mfa-codes', $recoveryCodes);
        }

        // get codes from session if present already:
        if (null !== $codesPreference) {
            $recoveryCodes = $codesPreference->data;
        }

        $codes = implode("\r\n", $recoveryCodes);

        $image = Google2FA::getQRCodeInline($domain, auth()->user()->email, $secret);

        return view('profile.code', compact('image', 'secret', 'codes'));
    }

    /**
     * Screen to confirm email change.
     *
     * @param UserRepositoryInterface $repository
     * @param string                  $token
     *
     * @return RedirectResponse|Redirector
     *
     * @throws FireflyException
     */
    public function confirmEmailChange(UserRepositoryInterface $repository, string $token): RedirectResponse | Redirector
    {
        if (!$this->internalAuth) {
            throw new FireflyException(trans('firefly.external_user_mgt_disabled'));
        }
        // find preference with this token value.
        /** @var Collection $set */
        $set  = app('preferences')->findByName('email_change_confirm_token');
        $user = null;
        /** @var Preference $preference */
        foreach ($set as $preference) {
            if ($preference->data === $token) {
                $user = $preference->user;
            }
        }
        // update user to clear blocked and blocked_code.
        if (null === $user) {
            throw new FireflyException('Invalid token.');
        }
        $repository->unblockUser($user);

        // return to log in.
        session()->flash('success', (string)trans('firefly.login_with_new_email'));

        return redirect(route('login'));
    }

    /**
     * Delete your account view.
     *
     * @param Request $request
     *
     * @return View|RedirectResponse
     */
    public function deleteAccount(Request $request): View | RedirectResponse
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }
        $title        = auth()->user()->email;
        $subTitle     = (string)trans('firefly.delete_account');
        $subTitleIcon = 'fa-trash';

        return view('profile.delete-account', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Delete 2FA routine.
     *
     */
    public function deleteCode(Request $request): RedirectResponse | Redirector
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        /** @var User $user */
        $user = auth()->user();

        Preferences::delete('temp-mfa-secret');
        Preferences::delete('temp-mfa-codes');
        $repository->setMFACode($user, null);
        app('preferences')->mark();

        session()->flash('success', (string)trans('firefly.pref_two_factor_auth_disabled'));
        session()->flash('info', (string)trans('firefly.pref_two_factor_auth_remove_it'));

        return redirect(route('profile.index'));
    }

    /**
     * Enable 2FA screen.
     *
     */
    public function enable2FA(Request $request): RedirectResponse | Redirector
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        /** @var User $user */
        $user       = auth()->user();
        $enabledMFA = null !== $user->mfa_secret;

        // if we don't have a valid secret yet, redirect to the code page to get one.
        if (!$enabledMFA) {
            return redirect(route('profile.code'));
        }

        // If FF3 already has a secret, just set the two factor auth enabled to 1,
        // and let the user continue with the existing secret.
        session()->flash('info', (string)trans('firefly.2fa_already_enabled'));

        return redirect(route('profile.index'));
    }

    /**
     * Index for profile.
     *
     * @return Factory|View
     * @throws FireflyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): Factory | View
    {
        /** @var User $user */
        $user           = auth()->user();
        $isInternalAuth = $this->internalAuth;
        $count          = DB::table('oauth_clients')->where('personal_access_client', true)->whereNull('user_id')->count();
        $subTitle       = $user->email;
        $userId         = $user->id;
        $enabled2FA     = null !== $user->mfa_secret;
        $mfaBackupCount = count(app('preferences')->get('mfa_recovery', [])->data);
        $this->createOAuthKeys();

        if (0 === $count) {
            /** @var ClientRepository $repository */
            $repository = app(ClientRepository::class);
            $repository->createPersonalAccessClient(null, config('app.name') . ' Personal Access Client', 'http://localhost');
        }

        $accessToken = app('preferences')->get('access_token');
        if (null === $accessToken) {
            $token       = $user->generateAccessToken();
            $accessToken = app('preferences')->set('access_token', $token);
        }

        return view(
            'profile.index',
            compact('subTitle', 'mfaBackupCount', 'userId', 'accessToken', 'enabled2FA', 'isInternalAuth')
        );
    }

    /**
     * @return Factory|View|RedirectResponse
     */
    public function logoutOtherSessions(): Factory | View | RedirectResponse
    {
        if (!$this->internalAuth) {
            session()->flash('info', (string)trans('firefly.external_auth_disabled'));

            return redirect(route('profile.index'));
        }

        return view('profile.logout-other-sessions');
    }

    /**
     * @param Request $request
     *
     * @return Factory|View|RedirectResponse
     * @throws FireflyException
     */
    public function newBackupCodes(Request $request): Factory | View | RedirectResponse
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        // generate recovery codes:
        $recovery      = app(Recovery::class);
        $recoveryCodes = $recovery->lowercase()
                                  ->setCount(8)     // Generate 8 codes
                                  ->setBlocks(2)    // Every code must have 7 blocks
                                  ->setChars(6)     // Each block must have 16 chars
                                  ->toArray();
        $codes         = implode("\r\n", $recoveryCodes);

        app('preferences')->set('mfa_recovery', $recoveryCodes);
        app('preferences')->mark();

        return view('profile.new-backup-codes', compact('codes'));
    }

    /**
     * Submit the change email form.
     *
     * @param EmailFormRequest        $request
     * @param UserRepositoryInterface $repository
     *
     * @return Factory|RedirectResponse|Redirector
     */
    public function postChangeEmail(EmailFormRequest $request, UserRepositoryInterface $repository): Factory | RedirectResponse | Redirector
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        /** @var User $user */
        $user     = auth()->user();
        $newEmail = $request->convertString('email');
        $oldEmail = $user->email;
        if ($newEmail === $user->email) {
            session()->flash('error', (string)trans('firefly.email_not_changed'));

            return redirect(route('profile.change-email'))->withInput();
        }
        $existing = $repository->findByEmail($newEmail);
        if (null !== $existing) {
            // force user logout.
            Auth::guard()->logout(); // @phpstan-ignore-line (does not recognize function)
            $request->session()->invalidate();

            session()->flash('success', (string)trans('firefly.email_changed'));

            return redirect(route('index'));
        }

        // now actually update user:
        $repository->changeEmail($user, $newEmail);

        event(new UserChangedEmail($user, $newEmail, $oldEmail));

        // force user logout.
        Auth::guard()->logout(); // @phpstan-ignore-line (does not recognize function)
        $request->session()->invalidate();
        session()->flash('success', (string)trans('firefly.email_changed'));

        return redirect(route('index'));
    }

    /**
     * Change your email address.
     *
     * @param Request $request
     *
     * @return Factory|RedirectResponse|View
     */
    public function changeEmail(Request $request): Factory | RedirectResponse | View
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        $title        = auth()->user()->email;
        $email        = auth()->user()->email;
        $subTitle     = (string)trans('firefly.change_your_email');
        $subTitleIcon = 'fa-envelope';

        return view('profile.change-email', compact('title', 'subTitle', 'subTitleIcon', 'email'));
    }

    /**
     * Submit change password form.
     *
     * @param ProfileFormRequest      $request
     * @param UserRepositoryInterface $repository
     *
     * @return RedirectResponse|Redirector
     */
    public function postChangePassword(ProfileFormRequest $request, UserRepositoryInterface $repository)
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        // the request has already validated both new passwords must be equal.
        $current = $request->get('current_password');
        $new     = $request->get('new_password');
        /** @var User $user */
        $user = auth()->user();
        try {
            $this->validatePassword($user, $current, $new);
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect(route('profile.change-password'));
        }

        $repository->changePassword($user, $request->get('new_password'));
        session()->flash('success', (string)trans('firefly.password_changed'));

        return redirect(route('profile.index'));
    }

    /**
     * Change your password.
     *
     * @param Request $request
     *
     * @return Factory|RedirectResponse|Redirector|View
     */
    public function changePassword(Request $request)
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        $title        = auth()->user()->email;
        $subTitle     = (string)trans('firefly.change_your_password');
        $subTitleIcon = 'fa-key';

        return view('profile.change-password', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Submit 2FA for the first time.
     *
     * @param TokenFormRequest $request
     *
     * @return RedirectResponse|Redirector
     * @throws FireflyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function postCode(TokenFormRequest $request)
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        /** @var User $user */
        $user = auth()->user();
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $secret     = Preferences::get('temp-mfa-secret')?->data;

        $repository->setMFACode($user, $secret);

        Preferences::delete('temp-mfa-secret');
        Preferences::delete('temp-mfa-codes');

        session()->flash('success', (string)trans('firefly.saved_preferences'));
        app('preferences')->mark();

        // also save the code so replay attack is prevented.
        $mfaCode = $request->get('code');
        $this->addToMFAHistory($mfaCode);

        // save backup codes in preferences:
        app('preferences')->set('mfa_recovery', session()->get('temp-mfa-codes'));

        // make sure MFA is logged out.
        if ('testing' !== config('app.env')) {
            Google2FA::logout();
        }

        // drop all info from session:
        session()->forget(['temp-mfa-secret', 'two-factor-secret', 'temp-mfa-codes', 'two-factor-codes']);

        return redirect(route('profile.index'));
    }

    /**
     * TODO duplicate code.
     *
     * @param string $mfaCode
     *
     * @throws FireflyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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

    /**
     * Submit delete account.
     *
     * @param UserRepositoryInterface  $repository
     * @param DeleteAccountFormRequest $request
     *
     * @return RedirectResponse|Redirector
     */
    public function postDeleteAccount(UserRepositoryInterface $repository, DeleteAccountFormRequest $request)
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        if (!Hash::check($request->get('password'), auth()->user()->password)) {
            session()->flash('error', (string)trans('firefly.invalid_password'));

            return redirect(route('profile.delete-account'));
        }
        /** @var User $user */
        $user = auth()->user();
        Log::info(sprintf('User #%d has opted to delete their account', auth()->user()->id));
        // make repository delete user:
        auth()->logout();
        session()->flush();
        $repository->destroy($user);

        return redirect(route('index'));
    }

    /**
     * @param Request $request
     *
     * @return Application|RedirectResponse|Redirector
     * @throws AuthenticationException
     */
    public function postLogoutOtherSessions(Request $request)
    {
        if (!$this->internalAuth) {
            session()->flash('info', (string)trans('firefly.external_auth_disabled'));

            return redirect(route('profile.index'));
        }
        $creds = [
            'email'    => auth()->user()->email,
            'password' => $request->get('password'),
        ];
        if (Auth::once($creds)) {
            Auth::logoutOtherDevices($request->get('password'));
            session()->flash('info', (string)trans('firefly.other_sessions_logged_out'));

            return redirect(route('profile.index'));
        }
        session()->flash('error', (string)trans('auth.failed'));

        return redirect(route('profile.index'));
    }

    /**
     * Regenerate access token.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Redirector
     * @throws Exception
     */
    public function regenerate(Request $request)
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        /** @var User $user */
        $user  = auth()->user();
        $token = $user->generateAccessToken();
        app('preferences')->set('access_token', $token);
        session()->flash('success', (string)trans('firefly.token_regenerated'));

        return redirect(route('profile.index'));
    }

    /**
     * Undo change of user email address.
     *
     * @param UserRepositoryInterface $repository
     * @param string                  $token
     * @param string                  $hash
     *
     * @return RedirectResponse|Redirector
     *
     * @throws FireflyException
     */
    public function undoEmailChange(UserRepositoryInterface $repository, string $token, string $hash)
    {
        if (!$this->internalAuth) {
            throw new FireflyException(trans('firefly.external_user_mgt_disabled'));
        }

        // find preference with this token value.
        $set  = app('preferences')->findByName('email_change_undo_token');
        $user = null;
        /** @var Preference $preference */
        foreach ($set as $preference) {
            if ($preference->data === $token) {
                $user = $preference->user;
            }
        }
        if (null === $user) {
            throw new FireflyException('Invalid token.');
        }

        // found user.which email address to return to?
        $set = app('preferences')->beginsWith($user, 'previous_email_');
        /** @var string $match */
        $match = null;
        foreach ($set as $entry) {
            $hashed = hash('sha256', sprintf('%s%s', (string)config('app.key'), $entry->data));
            if ($hashed === $hash) {
                $match = $entry->data;
                break;
            }
        }
        if (null === $match) {
            throw new FireflyException('Invalid token.');
        }
        // change user back
        // now actually update user:
        $repository->changeEmail($user, $match);
        $repository->unblockUser($user);

        // return to login.
        session()->flash('success', (string)trans('firefly.login_with_old_email'));

        return redirect(route('login'));
    }
}
