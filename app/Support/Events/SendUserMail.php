<?php
/**
 * SendUserMail.php
 * Copyright (c) 2016 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Events;

use Exception;
use FireflyConfig;
use FireflyIII\User;
use Illuminate\Mail\Message;
use Log;
use Mail;
use Preferences;
use Swift_TransportException;

class SendUserMail
{
    /**
     * @param User   $user
     * @param string $ipAddress
     *
     * @return bool
     */
    public function sendConfirmation(User $user, string $ipAddress): bool
    {
        $mustConfirmAccount = FireflyConfig::get('must_confirm_account', config('firefly.configuration.must_confirm_account'))->data;
        if ($mustConfirmAccount === false) {
            Preferences::setForUser($user, 'user_confirmed', true);
            Preferences::setForUser($user, 'user_confirmed_last_mail', 0);
            Preferences::mark();

            return true;
        }
        $email = $user->email;
        $code  = str_random(16);
        $route = route('do_confirm_account', [$code]);
        Preferences::setForUser($user, 'user_confirmed', false);
        Preferences::setForUser($user, 'user_confirmed_last_mail', time());
        Preferences::setForUser($user, 'user_confirmed_code', $code);
        try {
            Mail::send(
                ['emails.confirm-account-html', 'emails.confirm-account-text'], ['route' => $route, 'ip' => $ipAddress],
                function (Message $message) use ($email) {
                    $message->to($email, $email)->subject('Please confirm your Firefly III account');
                }
            );
        } catch (Swift_TransportException $e) {
            Log::error($e->getMessage());
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        return true;
    }
}