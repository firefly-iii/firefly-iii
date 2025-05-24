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

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\EmailFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use FireflyIII\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Passport\ClientRepository;

/**
 * Class ProfileController.
 *
 * @method Guard guard()
 */
class ProfileController extends Controller
{
    use CreateStuff;

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
     * Screen to confirm email change.
     *
     * @throws FireflyException
     */
    public function confirmEmailChange(UserRepositoryInterface $repository, string $token): Redirector|RedirectResponse
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
        session()->flash('success', (string) trans('firefly.login_with_new_email'));

        return redirect(route('login'));
    }

    /**
     * Delete your account view.
     */
    public function deleteAccount(Request $request): RedirectResponse|View
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }
        $title        = auth()->user()->email;
        $subTitle     = (string) trans('firefly.delete_account');
        $subTitleIcon = 'fa-trash';

        return view('profile.delete-account', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Index for profile.
     *
     * @throws FireflyException
     */
    public function index(): Factory|View
    {
        /** @var User $user */
        $user           = auth()->user();
        $isInternalAuth = $this->internalAuth;
        $count          = DB::table('oauth_clients')->where('personal_access_client', true)->whereNull('user_id')->count();
        $subTitle       = $user->email;
        $userId         = $user->id;
        $enabled2FA     = null !== $user->mfa_secret;
        $recoveryData   = app('preferences')->get('mfa_recovery', [])->data;
        if (!is_array($recoveryData)) {
            $recoveryData = [];
        }
        $mfaBackupCount = count($recoveryData);
        $this->createOAuthKeys();

        if (0 === $count) {
            /** @var ClientRepository $repository */
            $repository = app(ClientRepository::class);
            $repository->createPersonalAccessClient(null, config('app.name').' Personal Access Client', 'http://localhost');
        }

        $accessToken    = app('preferences')->get('access_token');
        if (null === $accessToken) {
            $token       = $user->generateAccessToken();
            $accessToken = app('preferences')->set('access_token', $token);
        }

        return view(
            'profile.index',
            compact('subTitle', 'mfaBackupCount', 'userId', 'accessToken', 'enabled2FA', 'isInternalAuth')
        );
    }

    public function logoutOtherSessions(): Factory|RedirectResponse|View
    {
        if (!$this->internalAuth) {
            session()->flash('info', (string) trans('firefly.external_auth_disabled'));

            return redirect(route('profile.index'));
        }

        return view('profile.logout-other-sessions');
    }

    /**
     * Submit the change email form.
     */
    public function postChangeEmail(EmailFormRequest $request, UserRepositoryInterface $repository): Factory|Redirector|RedirectResponse
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
            session()->flash('error', (string) trans('firefly.email_not_changed'));

            return redirect(route('profile.change-email'))->withInput();
        }
        $existing = $repository->findByEmail($newEmail);
        if ($existing instanceof User) {
            // force user logout.
            Auth::guard()->logout(); // @phpstan-ignore-line (does not recognize function)
            $request->session()->invalidate();

            session()->flash('success', (string) trans('firefly.email_changed'));

            return redirect(route('index'));
        }

        // now actually update user:
        $repository->changeEmail($user, $newEmail);

        event(new UserChangedEmail($user, $newEmail, $oldEmail));

        // force user logout.
        Auth::guard()->logout(); // @phpstan-ignore-line (does not recognize function)
        $request->session()->invalidate();
        session()->flash('success', (string) trans('firefly.email_changed'));

        return redirect(route('index'));
    }

    /**
     * Change your email address.
     */
    public function changeEmail(Request $request): Factory|RedirectResponse|View
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        $title        = auth()->user()->email;
        $email        = auth()->user()->email;
        $subTitle     = (string) trans('firefly.change_your_email');
        $subTitleIcon = 'fa-envelope';

        return view('profile.change-email', compact('title', 'subTitle', 'subTitleIcon', 'email'));
    }

    /**
     * Submit change password form.
     *
     * @return Redirector|RedirectResponse
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
        $user    = auth()->user();

        try {
            $this->validatePassword($user, $current, $new);
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect(route('profile.change-password'));
        }

        $repository->changePassword($user, $request->get('new_password'));
        session()->flash('success', (string) trans('firefly.password_changed'));

        return redirect(route('profile.index'));
    }

    /**
     * Change your password.
     *
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function changePassword(Request $request)
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        $title        = auth()->user()->email;
        $subTitle     = (string) trans('firefly.change_your_password');
        $subTitleIcon = 'fa-key';

        return view('profile.change-password', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Submit delete account.
     *
     * @return Redirector|RedirectResponse
     */
    public function postDeleteAccount(UserRepositoryInterface $repository, DeleteAccountFormRequest $request)
    {
        if (!$this->internalAuth) {
            $request->session()->flash('error', trans('firefly.external_user_mgt_disabled'));

            return redirect(route('profile.index'));
        }

        if (!Hash::check($request->get('password'), auth()->user()->password)) {
            session()->flash('error', (string) trans('firefly.invalid_password'));

            return redirect(route('profile.delete-account'));
        }

        /** @var User $user */
        $user = auth()->user();
        app('log')->info(sprintf('User #%d has opted to delete their account', auth()->user()->id));
        // make repository delete user:
        auth()->logout();
        session()->flush();
        $repository->destroy($user);

        return redirect(route('index'));
    }

    /**
     * @return Application|Redirector|RedirectResponse
     *
     * @throws AuthenticationException
     */
    public function postLogoutOtherSessions(Request $request)
    {
        if (!$this->internalAuth) {
            session()->flash('info', (string) trans('firefly.external_auth_disabled'));

            return redirect(route('profile.index'));
        }
        $creds = [
            'email'    => auth()->user()->email,
            'password' => $request->get('password'),
        ];
        if (Auth::once($creds)) {
            Auth::logoutOtherDevices($request->get('password'));
            session()->flash('info', (string) trans('firefly.other_sessions_logged_out'));

            return redirect(route('profile.index'));
        }
        session()->flash('error', (string) trans('auth.failed'));

        return redirect(route('profile.index'));
    }

    /**
     * Regenerate access token.
     *
     * @return Redirector|RedirectResponse
     *
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
        session()->flash('success', (string) trans('firefly.token_regenerated'));

        return redirect(route('profile.index'));
    }

    /**
     * Undo change of user email address.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function undoEmailChange(UserRepositoryInterface $repository, string $token, string $hash)
    {
        if (!$this->internalAuth) {
            throw new FireflyException(trans('firefly.external_user_mgt_disabled'));
        }

        // find preference with this token value.
        $set   = app('preferences')->findByName('email_change_undo_token');
        $user  = null;

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
        $set   = app('preferences')->beginsWith($user, 'previous_email_');

        /** @var string $match */
        $match = null;
        foreach ($set as $entry) {
            $hashed = hash('sha256', sprintf('%s%s', (string) config('app.key'), $entry->data));
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

        // return to login page.
        session()->flash('success', (string) trans('firefly.login_with_old_email'));

        return redirect(route('login'));
    }
}
