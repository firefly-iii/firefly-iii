<?php
/**
 * ForgotPasswordController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Password;

/**
 * Class ForgotPasswordController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

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
     * Send a reset link to the given user.
     *
     * @param  Request                $request
     *
     * @param UserRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request, UserRepositoryInterface $repository)
    {
        $this->validate($request, ['email' => 'required|email']);

        // verify if the user is not a demo user. If so, we give him back an error.
        $user = User::where('email', $request->get('email'))->first();

        if (!is_null($user) && $repository->hasRole($user, 'demo')) {
            return back()->withErrors(['email' => trans('firefly.cannot_reset_demo_user')]);
        }

        $response = $this->broker()->sendResetLink($request->only('email'));

        if ($response === Password::RESET_LINK_SENT) {
            return back()->with('status', trans($response));
        }

        // If an error was returned by the password broker, we will get this message
        // translated so we can notify a user of the problem. We'll redirect back
        // to where the users came from so they can attempt this process again.
        return back()->withErrors(['email' => trans($response)]); // @codeCoverageIgnore
    }
}
