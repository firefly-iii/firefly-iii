<?php
/**
 * ProfileController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Auth;
use FireflyIII\Events\UserIsDeleted;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\User;
use Hash;
use Preferences;
use Session;

/**
 * Class ProfileController
 *
 * @package FireflyIII\Http\Controllers
 */
class ProfileController extends Controller
{
    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return \Illuminate\View\View
     */
    public function changePassword()
    {
        return view('profile.change-password')->with('title', Auth::user()->email)->with('subTitle', trans('firefly.change_your_password'))->with(
            'mainTitleIcon', 'fa-user'
        );
    }

    /**
     * @return \Illuminate\View\View
     */
    public function deleteAccount()
    {
        return view('profile.delete-account')->with('title', Auth::user()->email)->with('subTitle', trans('firefly.delete_account'))->with(
            'mainTitleIcon', 'fa-user'
        );
    }

    /**
     * @return \Illuminate\View\View
     *
     */
    public function index()
    {
        return view('profile.index')->with('title', trans('firefly.profile'))->with('subTitle', Auth::user()->email)->with('mainTitleIcon', 'fa-user');
    }

    /**
     * @param ProfileFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postChangePassword(ProfileFormRequest $request)
    {
        // old, new1, new2
        if (!Hash::check($request->get('current_password'), Auth::user()->password)) {
            Session::flash('error', strval(trans('firefly.invalid_current_password')));

            return redirect(route('profile.change-password'));
        }
        $result = $this->validatePassword($request->get('current_password'), $request->get('new_password'));
        if (!($result === true)) {
            Session::flash('error', $result);

            return redirect(route('profile.change-password'));
        }

        // update the user with the new password.
        Auth::user()->password = bcrypt($request->get('new_password'));
        Auth::user()->save();

        Session::flash('success', strval(trans('firefly.password_changed')));

        return redirect(route('profile'));
    }

    /**
     * @param DeleteAccountFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function postDeleteAccount(DeleteAccountFormRequest $request)
    {
        // old, new1, new2
        if (!Hash::check($request->get('password'), Auth::user()->password)) {
            Session::flash('error', strval(trans('firefly.invalid_password')));

            return redirect(route('profile.delete-account'));
        }

        // respond to deletion:
        event(new UserIsDeleted(Auth::user(), $request->ip()));

        // store some stuff for the future:
        $registration = Preferences::get('registration_ip_address')->data;
        $confirmation = Preferences::get('confirmation_ip_address')->data;

        // DELETE!
        $email = Auth::user()->email;
        Auth::user()->delete();
        Session::flush();
        Session::flash('gaEventCategory', 'user');
        Session::flash('gaEventAction', 'delete-account');

        // create a new user with the same email address so re-registration is blocked.
        $newUser = User::create(
            [
                'email'        => $email,
                'password'     => 'deleted',
                'blocked'      => 1,
                'blocked_code' => 'deleted',
            ]
        );
        if (strlen($registration) > 0) {
            Preferences::setForUser($newUser, 'registration_ip_address', $registration);

        }
        if (strlen($confirmation) > 0) {
            Preferences::setForUser($newUser, 'confirmation_ip_address', $confirmation);
        }

        return redirect(route('index'));
    }

    /**
     *
     * @param string $old
     * @param string $new1
     *
     * @return string|bool
     */
    protected function validatePassword(string $old, string $new1)
    {
        if ($new1 == $old) {
            return trans('firefly.should_change');
        }

        return true;

    }


}
