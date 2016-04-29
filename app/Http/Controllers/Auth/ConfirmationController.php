<?php
declare(strict_types = 1);
/**
 * ConfirmationController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers\Auth;

use Auth;
use FireflyIII\Events\ResendConfirmation;
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
     * @param string $code
     *
     * @return mixed
     * @throws FireflyException
     */
    public function doConfirmation(string $code)
    {
        // check user_confirmed_last_mail

        $database = Preferences::get('user_confirmed_code')->data;
        $time     = Preferences::get('user_confirmed_last_mail', 0)->data;
        $now      = time();
        $maxDiff  = config('firefly.confirmation_age');

        if ($database === $code && ($now - $time <= $maxDiff)) {
            Preferences::setForUser(Auth::user(), 'user_confirmed', true);
            Preferences::setForUser(Auth::user(), 'user_confirmed_confirmed', time());
            Session::flash('success', strval(trans('firefly.account_is_confirmed')));

            return redirect(route('home'));
        }
        throw new FireflyException(trans('firefly.invalid_activation_code'));
    }

    /**
     * @param Request $request
     */
    public function resendConfirmation(Request $request)
    {
        $time    = Preferences::get('user_confirmed_last_mail', 0)->data;
        $now     = time();
        $maxDiff = config('firefly.resend_confirmation');
        $owner   = env('SITE_OWNER', 'mail@example.com');
        $view    = 'auth.confirmation.no-resent';
        if ($now - $time > $maxDiff) {
            event(new ResendConfirmation(Auth::user(), $request->ip()));
            $view = 'auth.confirmation.resent';
        }

        return view($view, ['owner' => $owner]);
    }

}
