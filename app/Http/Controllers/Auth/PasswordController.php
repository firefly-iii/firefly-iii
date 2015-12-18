<?php namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Password;

/**
 * Class PasswordController
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Controllers\Auth
 */
class PasswordController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;


    protected $redirectPath = '/';

    /**
     * Create a new password controller instance.
     *
     * @codeCoverageIgnore
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
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function postEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $user = User::whereEmail($request->get('email'))->first();

        if (!is_null($user) && intval($user->blocked) === 1) {
            $response = 'passwords.blocked';
        } else {
            $response = Password::sendResetLink(
                $request->only('email'), function (Message $message) {
                $message->subject($this->getEmailSubject());
            }
            );
        }

        switch ($response) {
            case Password::RESET_LINK_SENT:
                return redirect()->back()->with('status', trans($response));

            case Password::INVALID_USER:
            case 'passwords.blocked':
                return redirect()->back()->withErrors(['email' => trans($response)]);

        }
    }

}
