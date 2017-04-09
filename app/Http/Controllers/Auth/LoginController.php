<?php
/**
 * LoginController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use Config;
use FireflyConfig;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Cookie\CookieJar;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Lang;

/**
 * @codeCoverageIgnore
 *
 * Class LoginController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class LoginController extends Controller
{

    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Handle a login request to the application.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);
        $lockedOut = $this->hasTooManyLoginAttempts($request);
        if ($lockedOut) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials            = $this->credentials($request);
        $credentials['blocked'] = 0; // must not be blocked.

        if ($this->guard()->attempt($credentials, $request->has('remember'))) {
            return $this->sendLoginResponse($request);
        }

        $errorMessage = $this->getBlockedError($credentials['email']);

        if (!$lockedOut) {
            $this->incrementLoginAttempts($request);
        }

        return $this->sendFailedLoginResponse($request, $errorMessage);
    }

    /**
     * @param Request   $request
     * @param CookieJar $cookieJar
     *
     * @return $this
     */
    public function logout(Request $request, CookieJar $cookieJar)
    {
        if (intval(getenv('SANDSTORM')) === 1) {
            return view('error')->with('message', strval(trans('firefly.sandstorm_not_available')));
        }

        $cookie = $cookieJar->forever('twoFactorAuthenticated', 'false');

        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return redirect('/')->withCookie($cookie);
    }

    /**
     * @return string
     */
    public function redirectTo(): string
    {
        return route('index');
    }

    /**
     * Show the application login form.
     *
     * @param Request   $request
     *
     * @param CookieJar $cookieJar
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm(Request $request, CookieJar $cookieJar)
    {
        // forget 2fa cookie:
        $cookie = $cookieJar->forever('twoFactorAuthenticated', 'false');
        // is allowed to?
        $singleUserMode    = FireflyConfig::get('single_user_mode', Config::get('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $allowRegistration = true;
        if ($singleUserMode === true && $userCount > 0) {
            $allowRegistration = false;
        }

        $email    = $request->old('email');
        $remember = $request->old('remember');

        return view('auth.login', compact('allowRegistration', 'email', 'remember'))->withCookie($cookie);
    }

    /**
     * Get the failed login message.
     *
     * @param string $message
     *
     * @return string
     */
    protected function getFailedLoginMessage(string $message)
    {
        if (strlen($message) > 0) {
            return $message;
        }

        return Lang::has('auth.failed') ? Lang::get('auth.failed') : 'These credentials do not match our records.';
    }

    /**
     * Get the failed login response instance.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $message
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedLoginResponse(Request $request, string $message)
    {
        return redirect()->back()
                         ->withInput($request->only($this->username(), 'remember'))
                         ->withErrors(
                             [
                                 $this->username() => $this->getFailedLoginMessage($message),
                             ]
                         );
    }

    /**
     * @param string $email
     *
     * @return string
     */
    private function getBlockedError(string $email): string
    {
        // check if user is blocked:
        $errorMessage = '';
        /** @var User $foundUser */
        $foundUser = User::where('email', $email)->where('blocked', 1)->first();
        if (!is_null($foundUser)) {
            // user exists, but is blocked:
            $code         = strlen(strval($foundUser->blocked_code)) > 0 ? $foundUser->blocked_code : 'general_blocked';
            $errorMessage = strval(trans('firefly.' . $code . '_error', ['email' => $email]));
        }

        return $errorMessage;
    }
}
