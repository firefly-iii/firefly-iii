<?php
/**
 * PasswordController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Password;


/**
 * @codeCoverageIgnore
 *
 * Class PasswordController
 *
 * @package FireflyIII\Http\Controllers\Auth
 * @method getEmailSubject()
 * @method getSendResetLinkEmailSuccessResponse(string $response)
 * @method getSendResetLinkEmailFailureResponse(string $response)
 */
class PasswordController extends Controller
{

    use ResetsPasswords;

    /**
     * Create a new password controller instance.
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('guest');
    }

    /**
     * Send a reset link to the given user.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's 7 but ok
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $user     = User::whereEmail($request->get('email'))->first();
        $response = 'passwords.blocked';

        if (is_null($user)) {
            $response = Password::INVALID_USER;
        }

        if (!is_null($user) && intval($user->blocked) === 0) {
            $response = Password::sendResetLink(
                $request->only('email'), function (Message $message) {
                $message->subject($this->getEmailSubject());
            }
            );
        }

        switch ($response) {
            case Password::RESET_LINK_SENT:
                return $this->getSendResetLinkEmailSuccessResponse($response);

            case Password::INVALID_USER:
            case 'passwords.blocked':
            default:
                return $this->getSendResetLinkEmailFailureResponse($response);
        }
    }

}
