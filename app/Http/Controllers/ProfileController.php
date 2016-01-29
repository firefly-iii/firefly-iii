<?php namespace FireflyIII\Http\Controllers;

use Auth;
use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\Http\Requests\ProfileForm2FARequest;
use FireflyIII\Http\Requests\ProfileFormValidateQrCodeRequest;
use FireflyIII\User;
use Hash;
use Session;
use PragmaRX\Google2FA\Contracts\Google2FA;

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
    public function twoFactorAuth()
    {        
        return view('profile.two-factor-auth')->with('title', trans('firefly.profile'))->with('subTitle', trans('firefly.two_factor_auth_settings'))->with(
            'mainTitleIcon', 'fa-user'
        )->with('is_2fa_enabled', Auth::user()->is_2fa_enabled);
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
        Session::forget('user.secret_key') ;
        Session::forget('user.is_2fa_enabled');

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
            Session::flash('error', trans('firefly.invalid_current_password'));

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

        Session::flash('success', trans('firefly.password_changed'));

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
            Session::flash('error', trans('firefly.invalid_password'));

            return redirect(route('profile.delete-account'));
        }

        // DELETE!
        $email = Auth::user()->email;
        Auth::user()->delete();
        Session::flush();
        Session::flash('gaEventCategory', 'user');
        Session::flash('gaEventAction', 'delete-account');

        // create a new user with the same email address so re-registration is blocked.
        User::create(
            [
                'email'        => $email,
                'password'     => 'deleted',
                'blocked'      => 1,
                'blocked_code' => 'deleted',
            ]
        );

        return redirect(route('index'));
    }

    /**
     * @param ProfileFormRequestBase $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postTwoFactorAuth(ProfileForm2FARequest $request, Google2FA $google2fa)
    {
        $newValue = $request->get('enable_2fa');

        if($newValue != Auth::user()->is_2fa_enabled)
        {
            if($newValue == 1)
            {
                Session::set('user.is_2fa_enabled', 1);

                $secret = $google2fa->generateSecretKey(16, Auth::user()->id);
                Session::set('user.secret_key', $secret);


                $url = $google2fa->getQRCodeInline("FireflyIII", null, $secret, 150);

                return view('profile.validate-qr-code')->with('title', trans('firefly.profile'))
                            ->with('subTitle', trans('firefly.two_factor_auth_settings'))
                            ->with('mainTitleIcon', 'fa-user')
                            ->with('qrcode', $url)
                            ->with('secret', $secret);
                
            }else{

                Auth::user()->is_2fa_enabled = 0;
                Auth::user()->secret_key = null;

                Auth::user()->save();

                Session::forget('auth.2fa_passed');

                Session::flash('success', trans('firefly.two_factor_auth_settings_saved'));

                return redirect(route('profile'));              
            }
        }

        Session::flash('success', trans('firefly.two_factor_auth_settings_saved'));

        return redirect(route('profile'));        
    }


    /**
     * @param ProfileFormRequestBase $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postValidateQrCode(ProfileFormValidateQrCodeRequest $request, Google2FA $google2fa)
    {
        if(!Session::has('user.secret_key') || !Session::has('user.is_2fa_enabled'))
        {
            Session::flash('error', trans('firefly.two_factor_auth_failure'));
            return redirect(route('profile'));   
        }

        $code = $request->get('code');

        $valid = $google2fa->verifyKey(Session::get('user.secret_key'), $code);

        if($valid)
        {
            
            Auth::user()->is_2fa_enabled = Session::get('user.is_2fa_enabled');
            Auth::user()->secret_key = Session::get('user.secret_key');
            Auth::user()->save();

            // Set the session flag that indicates the the user has passed 2fa, other wise he will be redirected to the verify_token page.
            Session::put('auth.2fa_passed', 1);


            Session::flash('success', trans('firefly.two_factor_auth_settings_saved'));
            return redirect(route('profile'));
        }
        else
        {
            Session::flash('warning', trans('firefly.two_factor_auth_settings_failed'));

            $url = $google2fa->getQRCodeInline("FireflyIII", null, Session::get('user.secret_key'), 150);

            return view('profile.validate-qr-code')->with('title', trans('firefly.profile'))
                            ->with('subTitle', trans('firefly.enable_two_factor_auth'))
                            ->with('mainTitleIcon', 'fa-user')
                            ->with('qrcode', $url)
                            ->with('secret', Session::get('user.secret_key'));

        }


        Session::flash('success', trans('firefly.two_factor_auth_settings_saved'));

        return redirect(route('profile'));
    }

    /*
     *
     * @param string $old
     * @param string $new1
     *
     * @return string|bool
     */
    protected function validatePassword($old, $new1)
    {
        if ($new1 == $old) {
            return trans('firefly.should_change');
        }

        return true;
    }
}
