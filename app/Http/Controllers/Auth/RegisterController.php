<?php

namespace FireflyIII\Http\Controllers\Auth;

use Auth;
use Config;
use FireflyIII\Events\UserRegistration;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Facades\FireflyConfig;
use FireflyIII\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Log;
use Mail;
use Session;
use Swift_TransportException;
use Validator;

/**
 * Class RegisterController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function register(Request $request)
    {
        // is allowed to?
        $singleUserMode = FireflyConfig::get('single_user_mode', Config::get('firefly.configuration.single_user_mode'))->data;
        $userCount      = User::count();
        if ($singleUserMode === true && $userCount > 0) {
            $message = 'Registration is currently not available.';

            return view('error', compact('message'));
        }


        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException($request, $validator);
        }

        $data             = $request->all();
        $data['password'] = bcrypt($data['password']);

        // is user email domain blocked?
        if ($this->isBlockedDomain($data['email'])) {
            $validator->getMessageBag()->add('email', (string)trans('validation.invalid_domain'));

            $this->reportBlockedDomainRegistrationAttempt($data['email'], $request->ip());

            $this->throwValidationException($request, $validator);
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
     * OLD
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        // TODO move to FireflyConfig
        $showDemoWarning = env('SHOW_DEMO_WARNING', false);

        // is allowed to?
        $singleUserMode = FireflyConfig::get('single_user_mode', Config::get('firefly.configuration.single_user_mode'))->data;
        $userCount      = User::count();
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
                     'password' => 'required|min:6|confirmed',
                 ]
        );
    }

    /**
     * @return array
     */
    private function getBlockedDomains()
    {
        return FireflyConfig::get('blocked-domains', [])->data;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    private function isBlockedDomain(string $email)
    {
        $parts   = explode('@', $email);
        $blocked = $this->getBlockedDomains();

        if (isset($parts[1]) && in_array($parts[1], $blocked)) {
            return true;
        }

        return false;
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
