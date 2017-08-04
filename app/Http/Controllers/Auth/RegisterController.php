<?php
/**
 * RegisterController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use Auth;
use Config;
use FireflyConfig;
use FireflyIII\Events\RegisteredUser;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\UserRegistrationRequest;
use FireflyIII\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Session;
use Validator;

/**
 * @codeCoverageIgnore
 *
 * Class RegisterController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class RegisterController extends Controller
{

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
    public function register(UserRegistrationRequest $request)
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

        $user = $this->create($request->all());

        // trigger user registration event:
        event(new RegisteredUser($user, $request->ip()));

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
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm(Request $request)
    {
        // is demo site?
        $isDemoSite = FireflyConfig::get('is_demo_site', Config::get('firefly.configuration.is_demo_site'))->data;

        // is allowed to?
        $singleUserMode = FireflyConfig::get('single_user_mode', Config::get('firefly.configuration.single_user_mode'))->data;
        $userCount      = User::count();
        if ($singleUserMode === true && $userCount > 0) {
            $message = 'Registration is currently not available.';

            return view('error', compact('message'));
        }

        $email = $request->old('email');

        return view('auth.register', compact('isDemoSite', 'email'));
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
        /** @var User $user */
        $user = User::create(
            [
                'email'    => $data['email'],
                'password' => bcrypt($data['password']),
            ]
        );

        return $user;
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
}
