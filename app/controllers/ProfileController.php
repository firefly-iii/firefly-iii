<?php

use Firefly\Storage\User\UserRepositoryInterface as URI;

/**
 * Class ProfileController
 */
class ProfileController extends BaseController
{

    /**
     * @param URI $user
     */
    public function __construct(URI $user)
    {
        $this->user = $user;
    }

    /**
     * @return \Illuminate\View\View
     *
     */
    public function index()
    {
        View::share('title', 'Profile');
        View::share('subTitle', Auth::user()->email);
        View::share('mainTitleIcon', 'fa-user');
        return View::make('profile.index');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function changePassword()
    {
        View::share('title', Auth::user()->email);
        View::share('subTitle', 'Change your password');
        View::share('mainTitleIcon', 'fa-user');
        return View::make('profile.change-password');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postChangePassword()
    {

        // old, new1, new2
        /** @noinspection PhpUndefinedFieldInspection */
        if (!Hash::check(Input::get('old'), Auth::user()->password)) {
            Session::flash('error', 'Invalid current password!');

            return View::make('profile.change-password');
        }
        if (strlen(Input::get('new1')) == 0 || strlen(Input::get('new2')) == 0) {
            Session::flash('error', 'Do fill in a password!');

            return View::make('profile.change-password');
        }
        if (Input::get('new1') == Input::get('old')) {
            Session::flash('error', 'The idea is to change your password.');

            return View::make('profile.change-password');
        }

        if (Input::get('new1') !== Input::get('new2')) {
            Session::flash('error', 'New passwords do not match!');

            return View::make('profile.change-password');
        }

        // update the user with the new password.
        /** @noinspection PhpParamsInspection */
        $this->user->updatePassword(Auth::user(), Input::get('new1'));

        Session::flash('success', 'Password changed!');

        return Redirect::route('profile');
    }

} 