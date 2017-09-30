<?php
/**
 * AdminEventHandler.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\AdminRequestedTestMessage;
use FireflyIII\Mail\AdminTestMail;
use Log;
use Mail;
use Session;
use Swift_TransportException;

/**
 * Class AdminEventHandler
 *
 * @package FireflyIII\Handlers\Events
 */
class AdminEventHandler
{
    /**
     * @param AdminRequestedTestMessage $event
     *
     * @return bool
     */
    public function sendTestMessage(AdminRequestedTestMessage $event): bool
    {

        $email     = $event->user->email;
        $ipAddress = $event->ipAddress;

        Log::debug(sprintf('Now in sendTestMessage event handler. Email is %s, IP is %s', $email, $ipAddress));
        try {
            Log::debug('Trying to send message...');
            Mail::to($email)->send(new AdminTestMail($email, $ipAddress));
            // @codeCoverageIgnoreStart
        } catch (Swift_TransportException $e) {
            Log::debug('Send message failed! :(');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            Session::flash('error', 'Possible email error: ' . $e->getMessage());
        }
        Log::debug('If no error above this line, message was sent.');
        // @codeCoverageIgnoreEnd
        return true;
    }
}