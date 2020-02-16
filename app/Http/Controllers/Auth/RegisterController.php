<?php
/**
 * RegisterController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
/** @noinspection PhpDynamicAsStaticMethodCallInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Events\RegisteredUser;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use FireflyIII\Support\Http\Controllers\RequestInformation;
use FireflyIII\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Log;

/**
 * Class RegisterController
 *
 * This controller handles the registration of new users as well as their
 * validation and creation. By default this controller uses a trait to
 * provide this functionality without requiring any additional code.
 *
 * @codeCoverageIgnore
 */
class RegisterController extends Controller
{
    use RegistersUsers, RequestInformation, CreateStuff;

    /**
     * Where to redirect users after registration.
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
     * Handle a registration request for the application.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function register(Request $request)
    {
        // is allowed to?
        $allowRegistration = true;
        $loginProvider     = config('firefly.login_provider');
        $singleUserMode    = app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        if (true === $singleUserMode && $userCount > 0 && 'eloquent' === $loginProvider) {
            $allowRegistration = false;
        }

        if ('eloquent' !== $loginProvider) {
            $allowRegistration = false;
        }

        if (false === $allowRegistration) {
            $message = 'Registration is currently not available.';

            return view('error', compact('message'));
        }

        $this->validator($request->all())->validate();
        $user = $this->createUser($request->all());
        Log::info(sprintf('Registered new user %s', $user->email));
        event(new RegisteredUser($user, $request->ip()));

        $this->guard()->login($user);

        session()->flash('success', (string)trans('firefly.registered'));

        $this->registered($request, $user);

        return redirect($this->redirectPath());
    }

    /**
     * Show the application registration form.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showRegistrationForm(Request $request)
    {
        $allowRegistration = true;
        $loginProvider     = config('firefly.login_provider');
        $isDemoSite        = app('fireflyconfig')->get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;
        $singleUserMode    = app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $pageTitle         = (string)trans('firefly.register_page_title');

        if (true === $isDemoSite) {
            $allowRegistration = false;
        }

        if (true === $singleUserMode && $userCount > 0 && 'eloquent' === $loginProvider) {
            $allowRegistration = false;
        }

        if ('eloquent' !== $loginProvider) {
            $allowRegistration = false;
        }

        if (false === $allowRegistration) {
            $message = 'Registration is currently not available.';

            return view('error', compact('message'));
        }

        $email = $request->old('email');

        return view('auth.register', compact('isDemoSite', 'email', 'pageTitle'));
    }

}
