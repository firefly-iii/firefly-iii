<?php namespace FireflyIII\Http\Controllers\Auth;

use Auth;
use Config;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Log;
use Mail;
use Request as Rq;
use Session;
use Twig;
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
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout()
    {
        Auth::logout();

        return redirect('/auth/login');
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegister()
    {
        $host = Rq::getHttpHost();

        return view('auth.register', compact('host'));
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, [$this->loginUsername() => 'required', 'password' => 'required',]);
        $throttles = $this->isUsingThrottlesLoginsTrait();

        if ($throttles && $this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $credentials            = $this->getCredentials($request);
        $credentials['blocked'] = 0; // most not be blocked.

        if (Auth::attempt($credentials, $request->has('remember'))) {
            return $this->handleUserWasAuthenticated($request, $throttles);
        }

        $message = $this->getFailedLoginMessage();
        /** @var User $foundUser */
        $foundUser = User::where('email', $credentials['email'])->where('blocked', 1)->first();
        if (!is_null($foundUser)) {
            // if it exists, show message:
            $code = $foundUser->blocked_code;
            if (strlen($code) == 0) {
                $code = 'general_blocked';
            }
            $message = trans('firefly.' . $code . '_error', ['email' => $credentials['email']]);
        }

        if ($throttles) {
            $this->incrementLoginAttempts($request);
        }

        return redirect($this->loginPath())
            ->withInput($request->only($this->loginUsername(), 'remember'))
            ->withErrors([$this->loginUsername() => $message,]);
    }


    public $redirectTo = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @codeCoverageIgnore
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('guest', ['except' => 'getLogout']);
    }

    /**
     * Show the application login form.
     *
     * @codeCoverageIgnore
     * @return \Illuminate\Http\Response
     *
     */
    public function getLogin()
    {
        return Twig::render('auth.login');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRegister(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
            // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd


        $data             = $request->all();
        $data['password'] = bcrypt($data['password']);

        // is user email domain blocked?
        if ($this->isBlockedDomain($data['email'])) {
            $validator->getMessageBag()->add('email', trans('validation.invalid_domain'));
            $this->throwValidationException(
                $request, $validator
            );
        }

        Auth::login($this->create($data));

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
        // @codeCoverageIgnoreStart
        abort(500, 'Not a user!');

        return redirect('/');
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return array
     */
    protected function getBlockedDomains()
    {
        $set     = Config::get('mail.blocked_domains');
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
     * @param $email
     *
     * @return bool
     */
    protected function isBlockedDomain($email)
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
    public function validator(array $data)
    {
        return Validator::make(
            $data, [
                     'email'    => 'required|email|max:255|unique:users',
                     'password' => 'required|confirmed|min:6',
                 ]
        );
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     *
     * @return User
     */
    public function create(array $data)
    {
        return User::create(
            [
                'email'    => $data['email'],
                'password' => $data['password'],
            ]
        );
    }
}
