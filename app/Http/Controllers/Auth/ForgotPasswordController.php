<?php
/**
 * ForgotPasswordController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Log;

/**
 * Class ForgotPasswordController
 * @codeCoverageIgnore
 */
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @param UserRepositoryInterface   $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request, UserRepositoryInterface $repository)
    {
        Log::info('Start of sendResetLinkEmail()');
        $loginProvider = config('firefly.login_provider');
        // @codeCoverageIgnoreStart
        if ('eloquent' !== $loginProvider) {
            $message = sprintf('Cannot reset password when authenticating over "%s".', $loginProvider);
            Log::error($message);

            return view('error', compact('message'));
        }
        // @codeCoverageIgnoreEnd

        $this->validateEmail($request);

        // verify if the user is not a demo user. If so, we give him back an error.
        /** @var User $user */
        $user = User::where('email', $request->get('email'))->first();

        if (null !== $user && $repository->hasRole($user, 'demo')) {
            return back()->withErrors(['email' => (string)trans('firefly.cannot_reset_demo_user')]);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        if ($response === Password::RESET_LINK_SENT) {
            return back()->with('status', trans($response));
        }

        return back()->withErrors(['email' => trans($response)]); // @codeCoverageIgnore
    }

    /**
     * Show form for email recovery.
     *
     * @codeCoverageIgnore
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        $loginProvider = config('firefly.login_provider');
        if ('eloquent' !== $loginProvider) {
            $message = sprintf('Cannot reset password when authenticating over "%s".', $loginProvider);

            return view('error', compact('message'));
        }

        // is allowed to?
        $singleUserMode    = app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $allowRegistration = true;
        $pageTitle         = (string)trans('firefly.forgot_pw_page_title');
        if (true === $singleUserMode && $userCount > 0) {
            $allowRegistration = false;
        }

        return view('auth.passwords.email')->with(compact('allowRegistration', 'pageTitle'));
    }
}
