<?php namespace FireflyIII\Http\Controllers;

use Auth;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use Hash;
use Session;

/**
 * Class ProfileController
 *
 * @package FireflyIII\Http\Controllers
 */
class ProfileController extends Controller
{

    /**
     * @return \Illuminate\View\View
     */
    public function changePassword()
    {
        return view('profile.change-password')->with('title', Auth::user()->email)->with('subTitle', 'Change your password')->with(
            'mainTitleIcon', 'fa-user'
        );
    }

    /**
     * @return \Illuminate\View\View
     */
    public function deleteAccount()
    {
        return view('profile.delete-account')->with('title', Auth::user()->email)->with('subTitle', 'Delete account')->with(
            'mainTitleIcon', 'fa-user'
        );
    }

    /**
     * @return \Illuminate\View\View
     *
     */
    public function index()
    {
        return view('profile.index')->with('title', 'Profile')->with('subTitle', Auth::user()->email)->with('mainTitleIcon', 'fa-user');
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
            Session::flash('error', 'Invalid current password!');

            return redirect(route('profile.change-password'));
        }
        $result = $this->validatePassword($request->get('current_password'), $request->get('new_password'));
        if (!($result === true)) {
            Session::flash('error', $result);

            return redirect(route('profile.change-password'));
        }

        // update the user with the new password.
        Auth::user()->password = $request->get('new_password');
        Auth::user()->save();

        Session::flash('success', 'Password changed!');

        return redirect(route('profile'));
    }

    /**
     *
     * @param string $old
     * @param string $new1
     *
     * @return string|bool
     */
    protected function validatePassword($old, $new1)
    {
        if ($new1 == $old) {
            return 'The idea is to change your password.';
        }

        return true;

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
            Session::flash('error', 'Invalid password!');

            return redirect(route('profile.delete-account'));
        }

        // DELETE!
        Auth::user()->delete();
        Session::flush();
        Session::flash('gaEventCategory', 'user');
        Session::flash('gaEventAction', 'delete-account');

        return redirect(route('index'));
    }


}
