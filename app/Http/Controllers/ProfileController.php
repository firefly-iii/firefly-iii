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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Auth;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Http\Middleware\IsLimitedUser;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\EmailFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Hash;
use Illuminate\Contracts\Auth\Guard;
use Log;
use Preferences;
use Session;
use View;

/**
 * Class ProfileController.
 *
 * @method Guard guard()
 */
class ProfileController extends Controller
{
    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.profile'));
                View::share('mainTitleIcon', 'fa-user');

                return $next($request);
            }
        );
        $this->middleware(IsLimitedUser::class)->except(['confirmEmailChange', 'undoEmailChange']);
    }

    /**
     * @return View
     */
    public function changeEmail()
    {
        $title        = auth()->user()->email;
        $email        = auth()->user()->email;
        $subTitle     = strval(trans('firefly.change_your_email'));
        $subTitleIcon = 'fa-envelope';

        return view('profile.change-email', compact('title', 'subTitle', 'subTitleIcon', 'email'));
    }

    /**
     * @return View
     */
    public function changePassword()
    {
        $title        = auth()->user()->email;
        $subTitle     = strval(trans('firefly.change_your_password'));
        $subTitleIcon = 'fa-key';

        return view('profile.change-password', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param string $token
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function confirmEmailChange(string $token)
    {
        // find preference with this token value.
        $set  = Preferences::findByName('email_change_confirm_token');
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
        $user->blocked      = 0;
        $user->blocked_code = '';
        $user->save();

        // return to login.
        Session::flash('success', strval(trans('firefly.login_with_new_email')));

        return redirect(route('login'));
    }

    /**
     * @return View
     */
    public function deleteAccount()
    {
        $title        = auth()->user()->email;
        $subTitle     = strval(trans('firefly.delete_account'));
        $subTitleIcon = 'fa-trash';

        return view('profile.delete-account', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @return View
     */
    public function index()
    {
        $subTitle = auth()->user()->email;
        $userId   = auth()->user()->id;

        // get access token or create one.
        $accessToken = Preferences::get('access_token', null);
        if (null === $accessToken) {
            $token       = auth()->user()->generateAccessToken();
            $accessToken = Preferences::set('access_token', $token);
        }

        return view('profile.index', compact('subTitle', 'userId', 'accessToken'));
    }

    /**
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
            Session::flash('error', strval(trans('firefly.email_not_changed')));

            return redirect(route('profile.change-email'))->withInput();
        }
        $existing = $repository->findByEmail($newEmail);
        if (null !== $existing) {
            // force user logout.
            $this->guard()->logout();
            $request->session()->invalidate();

            Session::flash('success', strval(trans('firefly.email_changed')));

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
        Session::flash('success', strval(trans('firefly.email_changed')));

        return redirect(route('index'));
    }

    /**
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

        try {
            $this->validatePassword(auth()->user(), $current, $new);
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());

            return redirect(route('profile.change-password'));
        }

        $repository->changePassword(auth()->user(), $request->get('new_password'));
        Session::flash('success', strval(trans('firefly.password_changed')));

        return redirect(route('profile.index'));
    }

    /**
     * @param UserRepositoryInterface  $repository
     * @param DeleteAccountFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postDeleteAccount(UserRepositoryInterface $repository, DeleteAccountFormRequest $request)
    {
        if (!Hash::check($request->get('password'), auth()->user()->password)) {
            Session::flash('error', strval(trans('firefly.invalid_password')));

            return redirect(route('profile.delete-account'));
        }
        $user = auth()->user();
        Log::info(sprintf('User #%d has opted to delete their account', auth()->user()->id));
        // make repository delete user:
        auth()->logout();
        Session::flush();
        $repository->destroy($user);

        Session::flash('gaEventCategory', 'user');
        Session::flash('gaEventAction', 'delete-account');

        return redirect(route('index'));
    }

    /**
     *
     */
    public function regenerate()
    {
        $token = auth()->user()->generateAccessToken();
        Preferences::set('access_token', $token);
        Session::flash('success', strval(trans('firefly.token_regenerated')));

        return redirect(route('profile.index'));
    }

    /**
     * @param string $token
     * @param string $hash
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function undoEmailChange(string $token, string $hash)
    {
        // find preference with this token value.
        $set  = Preferences::findByName('email_change_undo_token');
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

        // found user.
        // which email address to return to?
        $set   = Preferences::beginsWith($user, 'previous_email_');
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
        $user->email        = $match;
        $user->blocked      = 0;
        $user->blocked_code = '';
        $user->save();

        // return to login.
        Session::flash('success', strval(trans('firefly.login_with_old_email')));

        return redirect(route('login'));
    }

    /**
     * @param User   $user
     * @param string $current
     * @param string $new
     *
     * @return bool
     *
     * @throws ValidationException
     */
    protected function validatePassword(User $user, string $current, string $new): bool
    {
        if (!Hash::check($current, $user->password)) {
            throw new ValidationException(strval(trans('firefly.invalid_current_password')));
        }

        if ($current === $new) {
            throw new ValidationException(strval(trans('firefly.should_change')));
        }

        return true;
    }
}
