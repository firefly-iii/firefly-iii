<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Auth;

use Auth;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Lang;
use Log;
use Mail;
use Request as Rq;
use Session;
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
        $message = '';
        /** @var User $foundUser */
        $foundUser = User::where('email', $credentials['email'])->where('blocked', 1)->first();
        if (!is_null($foundUser)) {
            // if it exists, show message:
            $code = $foundUser->blocked_code ?? '';

            if (strlen($code) == 0) {
                $code = 'general_blocked';
            }
            $message = trans('firefly.' . $code . '_error', ['email' => $credentials['email']]);
        }

        if ($throttles) {
            $this->incrementLoginAttempts($request);
        }

        return $this->sendFailedLoginResponse($request, $message);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
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
            $this->throwValidationException(
                $request, $validator
            );
        }


        Auth::login($this->create($request->all()));

        // get the email address
        if (Auth::user() instanceof User) {
            $email     = Auth::user()->email;
            $address   = route('index');
            $ipAddress = $request->ip();
            // send email.
            try {
                Mail::send(
                    ['emails.registered-html', 'emails.registered'], ['address' => $address, 'ip' => $ipAddress], function (Message $message) use ($email) {
                    $message->to($email, $email)->subject('Welcome to Firefly III! ');
                }
                );
            } catch (\Swift_TransportException $e) {
                Log::error($e->getMessage());
            }

            // set flash message
            Session::flash('success', 'You have registered successfully!');
            Session::flash('gaEventCategory', 'user');
            Session::flash('gaEventAction', 'new-registration');

            // first user ever?
            if (User::count() == 1) {
                $admin = Role::where('name', 'owner')->first();
                Auth::user()->attachRole($admin);
            }


            return redirect($this->redirectPath());
        }
        abort(500, 'Not a user!');


        return redirect($this->redirectPath());
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        $host = Rq::getHttpHost();

        return view('auth.register', compact('host'));
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
}
