<?php
/**
 * RegisterController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);


/**
 * RegisterController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers\Auth;

use FireflyConfig;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Session;

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
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
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
        // is allowed to?
        $singleUserMode = FireflyConfig::get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount      = User::count();
        if ($singleUserMode === true && $userCount > 0) {
            $message = 'Registration is currently not available.';

            return view('error', compact('message'));
        }

        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        Session::flash('success', strval(trans('firefly.registered')));

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }

    /**
     * Show the application registration form.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm(Request $request)
    {
        // is demo site?
        $isDemoSite = FireflyConfig::get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;

        // is allowed to?
        $singleUserMode = FireflyConfig::get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
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
     * @return \FireflyIII\User
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
            $data,
            [
                     'email'    => 'required|string|email|max:255|unique:users',
                     'password' => 'required|string|secure_password|confirmed',
                 ]
        );
    }
}
