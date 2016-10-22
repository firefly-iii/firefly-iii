<?php
/**
 * ConfirmationController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Events\ResentConfirmation;
use FireflyIII\Events\ConfirmedUser;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Preferences;
use Session;

/**
 * Class ConfirmationController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class ConfirmationController extends Controller
{

    /**
     * @return mixed
     */
    public function confirmationError()
    {
        return view('auth.confirmation.error');
    }

    /**
     * @param Request $request
     * @param string  $code
     *
     * @return mixed
     * @throws FireflyException
     */
    public function doConfirmation(Request $request, string $code)
    {
        // check user_confirmed_last_mail

        $database = Preferences::get('user_confirmed_code')->data;
        $time     = Preferences::get('user_confirmed_last_mail', 0)->data;
        $now      = time();
        $maxDiff  = config('firefly.confirmation_age');

        if ($database === $code && ($now - $time <= $maxDiff)) {

            // trigger user registration event:
            event(new ConfirmedUser(auth()->user(), $request->ip()));

            Preferences::setForUser(auth()->user(), 'user_confirmed', true);
            Preferences::setForUser(auth()->user(), 'user_confirmed_confirmed', time());
            Session::flash('success', strval(trans('firefly.account_is_confirmed')));

            return redirect(route('home'));
        }
        throw new FireflyException(trans('firefly.invalid_activation_code'));
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function resendConfirmation(Request $request)
    {
        $time    = Preferences::get('user_confirmed_last_mail', 0)->data;
        $now     = time();
        $maxDiff = config('firefly.resend_confirmation');
        $owner   = env('SITE_OWNER', 'mail@example.com');
        $view    = 'auth.confirmation.no-resent';
        if ($now - $time > $maxDiff) {
            event(new ResentConfirmation(auth()->user(), $request->ip()));
            $view = 'auth.confirmation.resent';
        }

        return view($view, ['owner' => $owner]);
    }

}
