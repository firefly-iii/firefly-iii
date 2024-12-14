<?php

/**
 * LoginController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

use Cookie;
use FireflyIII\Events\ActuallyLoggedIn;
use FireflyIII\Events\Security\UnknownUserAttemptedLogin;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Providers\RouteServiceProvider;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Class LoginController
 *
 * This controller handles authenticating users for the application and
 * redirecting them to your home screen. The controller uses a trait
 * to conveniently provide its functionality to your applications.
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;
    use ThrottlesLogins;

    /**
     * Where to redirect users after login.
     */
    protected string $redirectTo = RouteServiceProvider::HOME;
    private UserRepositoryInterface $repository;

    private string $username;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->username   = 'email';
        $this->middleware('guest')->except('logout');
        $this->repository = app(UserRepositoryInterface::class);
    }

    /**
     * Handle a login request to the application.
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $username = $request->get($this->username());
        Log::channel('audit')->info(sprintf('User is trying to login using "%s"', $username));
        app('log')->debug('User is trying to login.');

        try {
            $this->validateLogin($request);
        } catch (ValidationException $e) {
            // basic validation exception.
            // report the failed login to the user if the count is 2 or 5.
            // TODO here be warning.
            return redirect(route('login'))
                ->withErrors(
                    [
                        $this->username => trans('auth.failed'),
                    ]
                )
                ->onlyInput($this->username)
            ;
        }
        app('log')->debug('Login data is present.');

        // Copied directly from AuthenticatesUsers, but with logging added:
        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            Log::channel('audit')->warning(sprintf('Login for user "%s" was locked out.', $request->get($this->username())));
            app('log')->error(sprintf('Login for user "%s" was locked out.', $request->get($this->username())));
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }
        // Copied directly from AuthenticatesUsers, but with logging added:
        if ($this->attemptLogin($request)) {
            Log::channel('audit')->info(sprintf('User "%s" has been logged in.', $request->get($this->username())));
            app('log')->debug(sprintf('Redirect after login is %s.', $this->redirectPath()));

            // if you just logged in, it can't be that you have a valid 2FA cookie.

            // send a custom login event because laravel will also fire a login event if a "remember me"-cookie
            // restores the event.
            event(new ActuallyLoggedIn($this->guard()->user()));

            return $this->sendLoginResponse($request);
        }
        app('log')->warning('Login attempt failed.');
        $username = (string) $request->get($this->username());
        if (null === $this->repository->findByEmail($username)) {
            // send event to owner.
            event(new UnknownUserAttemptedLogin($username));
        }

        // Copied directly from AuthenticatesUsers, but with logging added:
        // If the login attempt was unsuccessful we will increment the number of attempts
        // to log in and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);
        Log::channel('audit')->warning(sprintf('Login failed. Attempt for user "%s" failed.', $request->get($this->username())));

        $this->sendFailedLoginResponse($request);

        // @noinspection PhpUnreachableStatementInspection
        return response()->json([]);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * Get the failed login response instance.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @throws ValidationException
     */
    protected function sendFailedLoginResponse(Request $request): void
    {
        $exception             = ValidationException::withMessages(
            [
                $this->username() => [trans('auth.failed')],
            ]
        );
        $exception->redirectTo = route('login');

        throw $exception;
    }

    /**
     * Log the user out of the application.
     *
     * @return Redirector|RedirectResponse|Response
     */
    public function logout(Request $request)
    {
        $authGuard  = config('firefly.authentication_guard');
        $logoutUrl  = config('firefly.custom_logout_url');
        if ('remote_user_guard' === $authGuard && '' !== $logoutUrl) {
            return redirect($logoutUrl);
        }
        if ('remote_user_guard' === $authGuard && '' === $logoutUrl) {
            session()->flash('error', trans('firefly.cant_logout_guard'));
        }

        // also logout current 2FA tokens.
        $cookieName = config('google2fa.cookie_name', 'google2fa_token');
        \Cookie::forget($cookieName);

        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $this->loggedOut($request);

        return $request->wantsJson()
            ? new Response('', 204)
            : redirect('/');
    }

    /**
     * Show the application's login form.
     *
     * @return Application|Factory|Redirector|RedirectResponse|View
     *
     * @throws FireflyException
     */
    public function showLoginForm(Request $request)
    {
        Log::channel('audit')->info('Show login form (1.1).');

        $count             = \DB::table('users')->count();
        $guard             = config('auth.defaults.guard');
        $title             = (string)trans('firefly.login_page_title');

        if (0 === $count && 'web' === $guard) {
            return redirect(route('register'));
        }

        // is allowed to register, etc.
        $singleUserMode    = app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $allowRegistration = true;
        $allowReset        = true;
        if (true === $singleUserMode && $count > 0) {
            $allowRegistration = false;
        }

        // single user mode is ignored when the user is not using eloquent:
        if ('web' !== $guard) {
            $allowRegistration = false;
            $allowReset        = false;
        }

        $email             = $request->old('email');
        $remember          = $request->old('remember');

        $storeInCookie     = config('google2fa.store_in_cookie', false);
        if (false !== $storeInCookie) {
            $cookieName = config('google2fa.cookie_name', 'google2fa_token');
            \Cookie::queue(\Cookie::make($cookieName, 'invalid-'.time()));
        }
        $usernameField     = $this->username();

        return view('auth.login', compact('allowRegistration', 'email', 'remember', 'allowReset', 'title', 'usernameField'));
    }
}
