<?php
/**
 * AuthController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Auth;

use Auth;
use FireflyIII\Events\UserRegistration;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Lang;
use Log;
use Mail;
use Session;
use Swift_TransportException;
use Validator;


/**
 * Class AuthController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class AuthController extends Controller
{
    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new authentication controller instance.
     *
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
        parent::__construct();
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
        $this->validate($request, [$this->loginUsername() => 'required', 'password' => 'required',]);
        $throttles = $this->isUsingThrottlesLoginsTrait();

        if ($throttles && $this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $credentials            = $this->getCredentials($request);
        $credentials['blocked'] = 0; // most not be blocked.

        if (Auth::guard($this->getGuard())->attempt($credentials, $request->has('remember'))) {
            return $this->handleUserWasAuthenticated($request, $throttles);
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

        if ($throttles) {
            $this->incrementLoginAttempts($request);
        }

        return $this->sendFailedLoginResponse($request, $errorMessage);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws FireflyException
     * @throws \Illuminate\Foundation\Validation\ValidationException
     */
    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        $data             = $request->all();
        $data['password'] = bcrypt($data['password']);

        // is user email domain blocked?
        if ($this->isBlockedDomain($data['email'])) {
            $validator->getMessageBag()->add('email', (string)trans('validation.invalid_domain'));

            $this->reportBlockedDomainRegistrationAttempt($data['email'], $request->ip());

            $this->throwValidationException(
                $request, $validator
            );
        }


        $user = $this->create($request->all());

        // trigger user registration event:
        event(new UserRegistration($user, $request->ip()));

        Auth::login($user);

        Session::flash('success', strval(trans('firefly.registered')));
        Session::flash('gaEventCategory', 'user');
        Session::flash('gaEventAction', 'new-registration');

        return redirect($this->redirectPath());
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        $showDemoWarning = env('SHOW_DEMO_WARNING', false);

        return view('auth.register', compact('showDemoWarning'));
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     *
     * @return User
     */
    protected function create(array $data)
    {
        return User::create(
            [
                'email'    => $data['email'],
                'password' => bcrypt($data['password']),
            ]
        );
    }

    /**
     * @return array
     */
    protected function getBlockedDomains()
    {
        $set     = explode(',', env('BLOCKED_DOMAINS', ''));
        $domains = [];
        foreach ($set as $entry) {
            $domain = trim($entry);
            if (strlen($domain) > 0) {
                $domains[] = $domain;
            }
        }

        return $domains;
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

        return Lang::has('auth.failed')
            ? Lang::get('auth.failed')
            : 'These credentials do not match our records.';
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    protected function isBlockedDomain(string $email)
    {
        $parts   = explode('@', $email);
        $blocked = $this->getBlockedDomains();

        if (isset($parts[1]) && in_array($parts[1], $blocked)) {
            return true;
        }

        return false;
    }

    /**
     * Get the failed login response instance.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $message
     *
     * @return \Illuminate\Http\Response
     */
    protected function sendFailedLoginResponse(Request $request, string $message)
    {
        return redirect()->back()
                         ->withInput($request->only($this->loginUsername(), 'remember'))
                         ->withErrors(
                             [
                                 $this->loginUsername() => $this->getFailedLoginMessage($message),
                             ]
                         );
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make(
            $data, [
                     'email'    => 'required|email|max:255|unique:users',
                     'password' => 'required|confirmed|min:6',
                 ]
        );
    }

    /**
     * Send a message home about a blocked domain and the address attempted to register.
     *
     * @param string $registrationMail
     * @param string $ipAddress
     */
    private function reportBlockedDomainRegistrationAttempt(string $registrationMail, string $ipAddress)
    {
        try {
            $email  = env('SITE_OWNER', false);
            $parts  = explode('@', $registrationMail);
            $domain = $parts[1];
            $fields = [
                'email_address'  => $registrationMail,
                'blocked_domain' => $domain,
                'ip'             => $ipAddress,
            ];

            Mail::send(
                ['emails.blocked-registration-html', 'emails.blocked-registration'], $fields, function (Message $message) use ($email, $domain) {
                $message->to($email, $email)->subject('Blocked a registration attempt with domain ' . $domain . '.');
            }
            );
        } catch (Swift_TransportException $e) {
            Log::error($e->getMessage());
        }
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
                ['emails.blocked-login-html', 'emails.blocked-login'], $fields, function (Message $message) use ($email, $user) {
                $message->to($email, $email)->subject('Blocked a login attempt from ' . trim($user->email) . '.');
            }
            );
        } catch (Swift_TransportException $e) {
            Log::error($e->getMessage());
        }
    }
}
