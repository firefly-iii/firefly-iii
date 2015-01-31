<?php

/**
 * @SuppressWarnings("CamelCase") // I'm fine with this.
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
        $result = $this->_validatePassword(Input::get('old'), Input::get('new1'), Input::get('new2'));
        if (!($result === true)) {
            Session::flash('error', $result);

            return View::make('profile.change-password');
        }

        // update the user with the new password.
        /** @var \FireflyIII\Database\User\User $repository */
        $repository = \App::make('FireflyIII\Database\User\User');
        $repository->updatePassword(Auth::user(), Input::get('new1'));

        Session::flash('success', 'Password changed!');

        return Redirect::route('profile');
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @param string $old
     * @param string $new1
     * @param string $new2
     *
     * @return string|bool
     */
    protected function _validatePassword($old, $new1, $new2)
    {
        if (strlen($new1) == 0 || strlen($new2) == 0) {
            return 'Do fill in a password!';

        }
        if ($new1 == $old) {
            return 'The idea is to change your password.';
        }

        if ($new1 !== $new2) {
            return 'New passwords do not match!';
        }

        return true;

    }

} 
