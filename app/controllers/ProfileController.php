<?php

/**
 * @SuppressWarnings("CyclomaticComplexity") // It's all 5. So ok.
 *
 * Class ProfileController
 */
class ProfileController extends BaseController
{

    /**
     * @return \Illuminate\View\View
     */
    public function changePassword()
    {
        return View::make('profile.change-password')->with('title', Auth::user()->email)->with('subTitle', 'Change your password')->with(
            'mainTitleIcon', 'fa-user'
        );
    }

    /**
     * @return \Illuminate\View\View
     *
     */
    public function index()
    {
        return View::make('profile.index')->with('title', 'Profile')->with('subTitle', Auth::user()->email)->with('mainTitleIcon', 'fa-user');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postChangePassword()
    {

        // old, new1, new2
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
        /** @var \FireflyIII\Database\User\User $repository */
        $repository = \App::make('FireflyIII\Database\User\User');
        $repository->updatePassword(Auth::user(), Input::get('new1'));

        Session::flash('success', 'Password changed!');

        return Redirect::route('profile');
    }

} 