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
        if (!$this->user->auth()) {
            $rememberMe = Input::get('remember_me') == '1';
            $result = [];
            $data = [
                'email' => Input::get('email'),
                'password' => Input::get('password')
            ];


            if (Auth::attempt($data, $rememberMe)) {
                return Redirect::route('index');
            }
        }
        Session::flash('error', 'No good!');
        return View::make('user.login');
    }

    public function register()
    {
        if (Config::get('auth.allow_register') !== true) {
            return App::abort(404);
        }
        return View::make('user.register');
    }

    public function postRegister()
    {
        if (Config::get('auth.allow_register') !== true) {
            return App::abort(404);
        }
        $user = $this->user->register();
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

    public function verify($verification)
    {
        $user = $this->user->findByVerification($verification);
        if ($user) {
            $this->email->sendPasswordMail($user);
            return View::make('user.registered');
        }
        return View::make('error')->with('message', 'Yo no hablo verification code!');
    }

} 