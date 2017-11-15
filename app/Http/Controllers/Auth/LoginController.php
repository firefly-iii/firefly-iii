<?php
/**
 * LoginController.php
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
 * LoginController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers\Auth;

use FireflyConfig;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Cookie\CookieJar;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Schema;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm(Request $request, CookieJar $cookieJar)
    {
        // check for presence of tables:
        $hasTable = Schema::hasTable('users');

        if (!$hasTable) {
            $message = 'Firefly III could not find the "users" table. This is a strong indication your database credentials are wrong or the database has not been initialized. Did you follow the installation instructions correctly?';
            return view('error', compact('message'));
        }

        // check for presence of currency:
        $currency = TransactionCurrency::where('code', 'EUR')->first();
        if (is_null($currency)) {
            $message = 'Firefly III could not find the EURO currency. This is a strong indication the database has not been initialized correctly. Did you follow the installation instructions?';
            return view('error', compact('message'));
        }

        // forget 2fa cookie:
        $cookie = $cookieJar->forever('twoFactorAuthenticated', 'false');

        // is allowed to?
        $singleUserMode    = FireflyConfig::get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $allowRegistration = true;
        if ($singleUserMode === true && $userCount > 0) {
            $allowRegistration = false;
        }

        $email    = $request->old('email');
        $remember = $request->old('remember');

        return view('auth.login', compact('allowRegistration', 'email', 'remember'))->withCookie($cookie);
    }
}
