<?php

/**
 * ResetPasswordController.php
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
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Class ResetPasswordController
 *
 * This controller is responsible for handling password reset requests
 * and uses a simple trait to include this behavior. You're free to
 * explore this trait and override any methods you wish to tweak.
 */
class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
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

        if ('web' !== config('firefly.authentication_guard')) {
            throw new FireflyException('Using external identity provider. Cannot continue.');
        }
    }

    /**
     * Reset the given user's password.
     *
     * @return Factory|JsonResponse|RedirectResponse|View
     *
     * @throws ValidationException
     */
    public function reset(Request $request)
    {
        if ('web' !== config('firefly.authentication_guard')) {
            $message = sprintf('Cannot reset password when authenticating over "%s".', config('firefly.authentication_guard'));

            return view('error', compact('message'));
        }

        $rules    = [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:16|secure_password',
        ];

        $this->validate($request, $rules, $this->validationErrorMessages());

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request),
            function ($user, $password): void {
                $this->resetPassword($user, $password);
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return Password::PASSWORD_RESET === $response
            ? $this->sendResetResponse($request, $response)
            : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param null $token
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function showResetForm(Request $request, $token = null)
    {
        if ('web' !== config('firefly.authentication_guard')) {
            $message = sprintf('Cannot reset password when authenticating over "%s".', config('firefly.authentication_guard'));

            return view('error', compact('message'));
        }

        // is allowed to register?
        $singleUserMode    = app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $allowRegistration = true;
        $pageTitle         = (string) trans('firefly.reset_pw_page_title');
        if (true === $singleUserMode && $userCount > 0) {
            $allowRegistration = false;
        }

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email, 'allowRegistration' => $allowRegistration, 'pageTitle' => $pageTitle]
        );
    }
}
