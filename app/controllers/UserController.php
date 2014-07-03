<?php

use Firefly\Helper\Email\EmailHelperInterface as EHI;
use Firefly\Storage\User\UserRepositoryInterface as URI;

class UserController extends BaseController
{

    public function __construct(URI $user, EHI $email)
    {
        $this->user = $user;
        $this->email = $email;

    }

    public function login()
    {
        return View::make('user.login');
    }

    public function postLogin()
    {
        $rememberMe = Input::get('remember_me') == '1';
        $data = [
            'email'    => Input::get('email'),
            'password' => Input::get('password')
        ];
        if (Auth::attempt($data, $rememberMe)) {
            Session::flash('success', 'Logged in!');
            return Redirect::route('index');
        }
        Session::flash('error', 'No good!');
        return View::make('user.login');
    }

    public function register()
    {
        if (Config::get('auth.allow_register') !== true) {
            App::abort(404);
        }
        return View::make('user.register');
    }

    public function postRegister()
    {
        if (Config::get('auth.allow_register') !== true) {
            App::abort(404);
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

    public function logout()
    {
        Auth::logout();
        return Redirect::route('index');
    }

    public function remindme()
    {
        return View::make('user.remindme');
    }

    public function postRemindme()
    {
        $user = $this->user->findByEmail(Input::get('email'));
        if ($user) {
            if (Config::get('auth.verify_reset') === true) {
                $this->email->sendResetVerification($user);
                return View::make('user.verification-pending');
            }
            if (Config::get('auth.verify_reset') === false) {
                $this->email->sendPasswordMail($user);
                return View::make('user.registered');
            }
        }
        Session::flash('error', 'No good!');
        return View::make('user.remindme');

    }

    public function verify($verification)
    {
        $user = $this->user->findByVerification($verification);
        if ($user) {
            $this->email->sendPasswordMail($user);
            return View::make('user.registered');
        }
        return View::make('error')->with('message', 'Yo no hablo verification code!');
    }

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