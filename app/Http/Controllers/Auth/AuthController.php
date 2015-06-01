<?php namespace FireflyIII\Http\Controllers\Auth;

use App;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Role;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Registrar;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Mail;
use Session;
use Twig;

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
     * @param  \Illuminate\Contracts\Auth\Guard     $auth
     * @param  \Illuminate\Contracts\Auth\Registrar $registrar
     *
     * @codeCoverageIgnore
     *
     */
    public function __construct(Guard $auth, Registrar $registrar)
    {
        $this->auth      = $auth;
        $this->registrar = $registrar;

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
        $validator = $this->registrar->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
            // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd

        $data             = $request->all();
        $data['password'] = bcrypt($data['password']);

        $this->auth->login($this->registrar->create($data));

        // get the email address
        if ($this->auth->user() instanceof User) {
            $email = $this->auth->user()->email;

            // send email.
            Mail::send(
                'emails.registered', [], function (Message $message) use ($email) {
                $message->to($email, $email)->subject('Welcome to Firefly III!');
            }
            );

            // set flash message
            Session::flash('success', 'You have registered successfully!');
            Session::flash('gaEventCategory', 'user');
            Session::flash('gaEventAction', 'new-registration');

            // first user ever?
            if (User::count() == 1) {
                $admin = Role::where('name', 'owner')->first();
                $this->auth->user()->attachRole($admin);
//                $this->auth->user()->roles()->save($admin);
            }


            return redirect($this->redirectPath());
        }
        App::abort(500, 'Not a user!');

        return redirect('/');
    }

}
