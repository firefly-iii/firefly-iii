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
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Auth;

use Config;
use FireflyConfig;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Lang;
use Log;
use Mail;
use Swift_TransportException;

/**
 * Class LoginController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

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
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        $lockedOut = $this->hasTooManyLoginAttempts($request);
        if ($lockedOut) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials            = $this->credentials($request);
        $credentials['blocked'] = 0; // most not be blocked.

        if ($this->guard()->attempt($credentials, $request->has('remember'))) {
            return $this->sendLoginResponse($request);
        }

        // check if user is blocked:
        $errorMessage = '';
        /** @var User $foundUser */
        $foundUser = User::where('email', $credentials['email'])->where('blocked', 1)->first();
        if (!is_null($foundUser)) {
            // if it exists, show message:
            $code         = strlen(strval($foundUser->blocked_code)) > 0 ? $foundUser->blocked_code : 'general_blocked';
            $errorMessage = strval(trans('firefly.' . $code . '_error', ['email' => $credentials['email']]));
            $this->reportBlockedUserLoginAttempt($foundUser, $code, $request->ip());
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        if (!$lockedOut) {
            $this->incrementLoginAttempts($request);
        }

        return $this->sendFailedLoginResponse($request, $errorMessage);
    }

    /**
     * Show the application login form.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm(Request $request)
    {
        // is allowed to?
        $singleUserMode    = FireflyConfig::get('single_user_mode', Config::get('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $allowRegistration = true;
        if ($singleUserMode === true && $userCount > 0) {
            $allowRegistration = false;
        }

        $email    = $request->old('email');
        $remember = $request->old('remember');

        return view('auth.login', compact('allowRegistration', 'email', 'remember'));
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
     * Send a message home about the  blocked attempt to login.
     * Perhaps in a later stage, simply log these messages.
     *
     * @param User   $user
     * @param string $code
     * @param string $ipAddress
     */
    private function reportBlockedUserLoginAttempt(User $user, string $code, string $ipAddress)
    {

        try {
            $email  = env('SITE_OWNER', false);
            $fields = [
                'user_id'      => $user->id,
                'user_address' => $user->email,
                'code'         => $code,
                'ip'           => $ipAddress,
            ];

            Mail::send(
                ['emails.blocked-login-html', 'emails.blocked-login-text'], $fields, function (Message $message) use ($email, $user) {
                $message->to($email, $email)->subject('Blocked a login attempt from ' . trim($user->email) . '.');
            }
            );
        } catch (Swift_TransportException $e) {
            Log::error($e->getMessage());
        }
    }
}
