<?php
/**
 * ProfileController.php
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

namespace FireflyIII\Http\Controllers;

use Auth;
use DB;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Middleware\IsSandStormUser;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\EmailFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\Http\Requests\TokenFormRequest;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use FireflyIII\Support\Http\Controllers\RequestInformation;
use FireflyIII\User;
use Google2FA;
use Hash;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;
use Laravel\Passport\ClientRepository;
use Log;

/**
 * Class ProfileController.
 *
 * @method Guard guard()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProfileController extends Controller
{
    use RequestInformation, CreateStuff;

    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.profile'));
                app('view')->share('mainTitleIcon', 'fa-user');

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index']);
        $this->middleware(IsSandStormUser::class)->except('index');
    }

    /**
     * Change your email address.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function changeEmail()
    {
        $title        = auth()->user()->email;
        $email        = auth()->user()->email;
        $subTitle     = (string)trans('firefly.change_your_email');
        $subTitleIcon = 'fa-envelope';

        return view('profile.change-email', compact('title', 'subTitle', 'subTitleIcon', 'email'));
    }

    /**
     * Change your password.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function changePassword()
    {
        $title        = auth()->user()->email;
        $subTitle     = (string)trans('firefly.change_your_password');
        $subTitleIcon = 'fa-key';

        return view('profile.change-password', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * View that generates a 2FA code for the user.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function code()
    {
        $domain = $this->getDomain();
        $secret = Google2FA::generateSecretKey();
        session()->flash('two-factor-secret', $secret);

        $image = Google2FA::getQRCodeInline($domain, auth()->user()->email, $secret, 200);

        return view('profile.code', compact('image', 'secret'));
    }

    /**
     * Screen to confirm email change.
     *
     * @param UserRepositoryInterface $repository
     * @param string                  $token
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function confirmEmailChange(UserRepositoryInterface $repository, string $token)
    {
        // find preference with this token value.
        /** @var Collection $set */
        $set  = app('preferences')->findByName('email_change_confirm_token');
        $user = null;
        Log::debug(sprintf('Found %d preferences', $set->count()));
        /** @var Preference $preference */
        foreach ($set as $preference) {
            if ($preference->data === $token) {
                Log::debug('Found user');
                $user = $preference->user;
            }
        }
        // update user to clear blocked and blocked_code.
        if (null === $user) {
            Log::debug('Found no user');
            throw new FireflyException('Invalid token.');
        }
        Log::debug('Will unblock user.');
        $repository->unblockUser($user);

        // return to login.
        session()->flash('success', (string)trans('firefly.login_with_new_email'));

        return redirect(route('login'));
    }

    /**
     * Delete your account view.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function deleteAccount()
    {
        $title        = auth()->user()->email;
        $subTitle     = (string)trans('firefly.delete_account');
        $subTitleIcon = 'fa-trash';

        return view('profile.delete-account', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Delete 2FA routine.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function deleteCode()
    {
        app('preferences')->delete('twoFactorAuthEnabled');
        app('preferences')->delete('twoFactorAuthSecret');
        session()->flash('success', (string)trans('firefly.pref_two_factor_auth_disabled'));
        session()->flash('info', (string)trans('firefly.pref_two_factor_auth_remove_it'));

        return redirect(route('profile.index'));
    }

    /**
     * Enable 2FA screen.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function enable2FA()
    {
        $hasSecret = (null !== app('preferences')->get('twoFactorAuthSecret'));

        // if we don't have a valid secret yet, redirect to the code page to get one.
        if (!$hasSecret) {
            return redirect(route('profile.code'));
        }

        // If FF3 already has a secret, just set the two factor auth enabled to 1,
        // and let the user continue with the existing secret.

        app('preferences')->set('twoFactorAuthEnabled', 1);

        return redirect(route('profile.index'));
    }

    /**
     * Index for profile.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        // check if client token thing exists (default one)
        $count = DB::table('oauth_clients')
                   ->where('personal_access_client', 1)
                   ->whereNull('user_id')->count();

        $this->createOAuthKeys();

        if (0 === $count) {
            /** @var ClientRepository $repository */
            $repository = app(ClientRepository::class);
            $repository->createPersonalAccessClient(null, config('app.name') . ' Personal Access Client', 'http://localhost');
        }
        $subTitle   = auth()->user()->email;
        $userId     = auth()->user()->id;
        $enabled2FA = 1 === (int)app('preferences')->get('twoFactorAuthEnabled', 0)->data;
        /** @var User $user */
        $user = auth()->user();

        // get access token or create one.
        $accessToken = app('preferences')->get('access_token', null);
        if (null === $accessToken) {
            $token       = $user->generateAccessToken();
            $accessToken = app('preferences')->set('access_token', $token);
        }

        return view('profile.index', compact('subTitle', 'userId', 'accessToken', 'enabled2FA'));
    }

    /**
     * Submit the change email form.
     *
     * @param EmailFormRequest        $request
     * @param UserRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postChangeEmail(EmailFormRequest $request, UserRepositoryInterface $repository)
    {
        /** @var User $user */
        $user     = auth()->user();
        $newEmail = $request->string('email');
        $oldEmail = $user->email;
        if ($newEmail === $user->email) {
            session()->flash('error', (string)trans('firefly.email_not_changed'));

            return redirect(route('profile.change-email'))->withInput();
        }
        $existing = $repository->findByEmail($newEmail);
        if (null !== $existing) {
            // force user logout.
            Auth::guard()->logout();
            $request->session()->invalidate();

            session()->flash('success', (string)trans('firefly.email_changed'));

            return redirect(route('index'));
        }

        // now actually update user:
        $repository->changeEmail($user, $newEmail);

        // call event.
        $ipAddress = $request->ip();
        event(new UserChangedEmail($user, $newEmail, $oldEmail, $ipAddress));

        // force user logout.
        Auth::guard()->logout();
        $request->session()->invalidate();
        session()->flash('success', (string)trans('firefly.email_changed'));

        return redirect(route('index'));
    }

    /**
     * Submit change password form.
     *
     * @param ProfileFormRequest      $request
     * @param UserRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postChangePassword(ProfileFormRequest $request, UserRepositoryInterface $repository)
    {
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

    /** @noinspection PhpUnusedParameterInspection */
    /**
     * Submit 2FA for the first time.
     *
     * @param TokenFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postCode(TokenFormRequest $request)
    {
        app('preferences')->set('twoFactorAuthEnabled', 1);
        app('preferences')->set('twoFactorAuthSecret', session()->get('two-factor-secret'));

        session()->flash('success', (string)trans('firefly.saved_preferences'));
        app('preferences')->mark();

        return redirect(route('profile.index'));
    }

    /**
     * Submit delete account.
     *
     * @param UserRepositoryInterface  $repository
     * @param DeleteAccountFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postDeleteAccount(UserRepositoryInterface $repository, DeleteAccountFormRequest $request)
    {
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
     * Regenerate access token.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function regenerate()
    {
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
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function undoEmailChange(UserRepositoryInterface $repository, string $token, string $hash)
    {
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
            $hashed = hash('sha256', $entry->data);
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
