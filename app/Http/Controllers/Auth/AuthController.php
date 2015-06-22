<?php namespace FireflyIII\Http\Controllers\Auth;

use App;
use Auth;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Mail;
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

    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers;

    public $redirectTo = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @codeCoverageIgnore
     *
     */
    public function __construct()
    {
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

        Auth::login($this->create($data));

        // get the email address
        if (Auth::user() instanceof User) {
            $email   = Auth::user()->email;
            $address = route('index');
            // send email.
            Mail::send(
                ['emails.registered-html', 'emails.registered'], ['address' => $address], function (Message $message) use ($email) {
                $message->to($email, $email)->subject('Welcome to Firefly III! ');
            }
            );

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
        App::abort(500, 'Not a user!');

        return redirect('/');
        // @codeCoverageIgnoreEnd
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
