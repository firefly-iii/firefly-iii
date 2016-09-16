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
use Config;
use FireflyIII\Events\UserRegistration;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Facades\FireflyConfig;
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
        // is allowed to?
        $singleUserMode    = FireflyConfig::get('single_user_mode', Config::get('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        if ($singleUserMode === true && $userCount > 0) {
            $message = 'Registration is currently not available.';

            return view('error', compact('message'));
        }


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

        // is allowed to?
        $singleUserMode    = FireflyConfig::get('single_user_mode', Config::get('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        if ($singleUserMode === true && $userCount > 0) {
            $message = 'Registration is currently not available.';

            return view('error', compact('message'));
        }

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
        return FireflyConfig::get('blocked-domains', [])->data;
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
}
