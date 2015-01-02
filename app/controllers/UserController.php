<?php

/**
 * Class UserController
 */
class UserController extends BaseController
{

    /**
     * Constructor.
     */
    public function __construct()
    {
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
     * Logout user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::logout();
        Session::flush();

        return Redirect::route('login');
    }

    /**
     * Login.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postLogin()
    {
        $rememberMe = Input::get('remember_me') == '1';
        $data       = ['email' => Input::get('email'), 'password' => Input::get('password')];
        $result     = Auth::attempt($data, $rememberMe);
        if ($result) {
            return Redirect::route('index');
        }

        Session::flash('error', 'No good!');

        return View::make('user.login');
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

        /** @var \FireflyIII\Database\User\User $repository */
        $repository = App::make('FireflyIII\Database\User\User');

        /** @var \FireflyIII\Shared\Mail\RegistrationInterface $email */
        $email = App::make('FireflyIII\Shared\Mail\RegistrationInterface');

        $user = $repository->register(Input::all());

        if ($user) {
            $email->sendVerificationMail($user);

            return View::make('user.verification-pending');
        }


        return View::make('user.register');
    }

    /**
     * If need to verify, send new reset code.
     * Otherwise, send new password.
     *
     * @return \Illuminate\View\View
     */
    public function postRemindMe()
    {

        /** @var \FireflyIII\Database\User\User $repository */
        $repository = App::make('FireflyIII\Database\User\User');

        /** @var \FireflyIII\Shared\Mail\RegistrationInterface $email */
        $email = App::make('FireflyIII\Shared\Mail\RegistrationInterface');


        $user = $repository->findByEmail(Input::get('email'));
        if (!$user) {
            Session::flash('error', 'No good!');

            return View::make('user.remindMe');
        }
        $email->sendResetVerification($user);

        return View::make('user.verification-pending');

    }

    /**
     * If allowed, show the register form.
     *
     * @return $this|\Illuminate\View\View
     */
    public function register()
    {

        return View::make('user.register');
    }

    /**
     * Show form to help user get a new password.
     *
     * @return \Illuminate\View\View
     */
    public function remindMe()
    {
        return View::make('user.remindMe');
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

        /** @var \FireflyIII\Database\User\User $repository */
        $repository = App::make('FireflyIII\Database\User\User');

        /** @var \FireflyIII\Shared\Mail\RegistrationInterface $email */
        $email = App::make('FireflyIII\Shared\Mail\RegistrationInterface');

        $user = $repository->findByReset($reset);
        if ($user) {
            $email->sendPasswordMail($user);

            return View::make('user.registered');
        }

        return View::make('error')->with('message', 'No reset code found!');
    }

}
