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

use FireflyIII\Notifications\User\NewAccessToken;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Events\AccessTokenCreated;

/**
 * Class APIEventHandler
 */
class APIEventHandler
{
    /**
     * Respond to the creation of an access token.
     */
    public function accessTokenCreated(AccessTokenCreated $event): void
    {
        app('log')->debug(__METHOD__);

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->find((int)$event->userId);

        if (null !== $user) {
            try {
                Notification::send($user, new NewAccessToken());
            } catch (\Exception $e) { // @phpstan-ignore-line
                $message = $e->getMessage();
                if (str_contains($message, 'Bcc')) {
                    app('log')->warning('[Bcc] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                if (str_contains($message, 'RFC 2822')) {
                    app('log')->warning('[RFC] Could not send notification. Please validate your email settings, use the .env.example file as a guide.');

                    return;
                }
                app('log')->error($e->getMessage());
                app('log')->error($e->getTraceAsString());
            }
        }
    }
}
