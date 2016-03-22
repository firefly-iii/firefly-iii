<?php
/**
 * TwoFactorController.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers\Auth;

use Auth;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\TokenFormRequest;
use Log;
use Preferences;
use Session;

/**
 * Class TwoFactorController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class TwoFactorController extends Controller
{

    /**
     * @return mixed
     * @throws FireflyException
     */
    public function index()
    {
        $user = Auth::user();

        // to make sure the validator in the next step gets the secret, we push it in session
        $secret = Preferences::get('twoFactorAuthSecret', '')->data;

        if (strlen($secret) === 0) {
            throw new FireflyException('Your two factor authentication secret is empty, which it cannot be at this point. Please check the log files.');
        }
        Session::flash('two-factor-secret', $secret);

        return view('auth.two-factor', compact('user'));
    }

    /**
     * @return mixed
     * @throws FireflyException
     */
    public function lostTwoFactor()
    {
        $user      = Auth::user();
        $siteOwner = env('SITE_OWNER', '');

        Log::info(
            'To reset the two factor authentication for user #' . $user->id .
            ' (' . $user->email . '), simply open the "preferences" table and delete the entries with the names "twoFactorAuthEnabled" and' .
            ' "twoFactorAuthSecret" for user_id ' . $user->id . '. That will take care of it.'
        );

        return view('auth.lost-two-factor', compact('user', 'siteOwner'));
    }

    /**
     * @param TokenFormRequest $request
     *
     * @return mixed
     */
    public function postIndex(TokenFormRequest $request)
    {
        Session::put('twofactor-authenticated', true);
        Session::put('twofactor-authenticated-date', new Carbon);

        return redirect(route('home'));
    }

}