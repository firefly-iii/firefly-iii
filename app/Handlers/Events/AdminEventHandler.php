<?php
/**
 * AdminEventHandler.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
 * Class AdminEventHandler.
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
