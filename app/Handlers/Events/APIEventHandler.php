<?php
/**
 * APIEventHandler.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;


use Exception;
use FireflyIII\Mail\AccessTokenCreatedMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Laravel\Passport\Events\AccessTokenCreated;
use Log;
use Mail;
use Request;
use Session;

/**
 * Class APIEventHandler
 */
class APIEventHandler
{
    /**
     * Respond to the creation of an access token.
     *
     * @param AccessTokenCreated $event
     *
     * @return bool
     */
    public function accessTokenCreated(AccessTokenCreated $event): bool
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->findNull((int)$event->userId);
        if (null !== $user) {
            $email     = $user->email;
            $ipAddress = Request::ip();

            Log::debug(sprintf('Now in APIEventHandler::accessTokenCreated. Email is %s, IP is %s', $email, $ipAddress));
            try {
                Log::debug('Trying to send message...');
                Mail::to($email)->send(new AccessTokenCreatedMail($email, $ipAddress));
                // @codeCoverageIgnoreStart
            } catch (Exception $e) {
                Log::debug('Send message failed! :(');
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
                Session::flash('error', 'Possible email error: ' . $e->getMessage());
            }
            // @codeCoverageIgnoreEnd
            Log::debug('If no error above this line, message was sent.');
        }

        return true;


    }

}
