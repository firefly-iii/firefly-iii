<?php

use Firefly\Helper\Email\EmailHelperInterface as EHI;
use Firefly\Storage\User\UserRepositoryInterface as URI;

/**
 * Class UserController
 */
class UserController extends BaseController
{

    /**
     * Constructor.
     *
     * @param URI $user
     * @param EHI $email
     */
    public function __construct(URI $user, EHI $email)
    {
        $this->user = $user;
        $this->email = $email;

    }

    /**
     * Show the login view.
     *
     * @return \Illuminate\View\View
     */
    public function login()
    {
        return View::make('user.login');
    }


    /**
     * Login.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postLogin()
    {
        $rememberMe = Input::get('remember_me') == '1';
        $data = [
            'email'    => Input::get('email'),
            'password' => Input::get('password')
        ];
        $result = Auth::attempt($data, $rememberMe);
        if ($result) {
            Session::flash('success', 'Logged in!');
            return Redirect::route('index');
        }

        Session::flash('error', 'No good!');
        return View::make('user.login');
    }

    /**
     * If allowed, show the register form.
     *
     * @return $this|\Illuminate\View\View
     */
    public function register()
    {
        if (Config::get('auth.allow_register') !== true) {
            return View::make('error')->with('message', 'Not possible');
        }

        return View::make('user.register');
    }

    /**
     * If allowed, register the user.
     *
     * Then:
     *
     * - Send password OR
     * - Send reset code.
     *
     * @return $this|\Illuminate\View\View
     */
    public function postRegister()
    {
        if (Config::get('auth.allow_register') !== true) {
            return View::make('error')->with('message', 'Not possible');
        }
        $user = $this->user->register(Input::all());
        if ($user) {
            if (Config::get('auth.verify_mail') === true) {
                $this->email->sendVerificationMail($user);

                return View::make('user.verification-pending');
            }
            $this->email->sendPasswordMail($user);

            return View::make('user.registered');
        }

        return View::make('user.register');
    }

    /**
     * Logout user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::logout();
        Session::flush();

        return Redirect::route('index');
    }

    /**
     * Show form to help user get a new password.
     *
     * @return \Illuminate\View\View
     */
    public function remindme()
    {
        return View::make('user.remindme');
    }

    /**
     * If need to verify, send new reset code.
     * Otherwise, send new password.
     *
     * @return \Illuminate\View\View
     */
    public function postRemindme()
    {
        $user = $this->user->findByEmail(Input::get('email'));
        if (!$user) {
            Session::flash('error', 'No good!');

            return View::make('user.remindme');
        }
        if (Config::get('auth.verify_reset') === true) {
            $this->email->sendResetVerification($user);

            return View::make('user.verification-pending');
        }
        $this->email->sendPasswordMail($user);

        return View::make('user.registered');

    }

    /**
     * Send a user a password based on his reset code.
     *
     * @param $reset
     *
     * @return $this|\Illuminate\View\View
     */
    public function reset($reset)
    {
        $user = $this->user->findByReset($reset);
        if ($user) {
            $this->email->sendPasswordMail($user);

            return View::make('user.registered');
        }

        return View::make('error')->with('message', 'Yo no hablo reset code!');
    }

}