<?php
/**
 * AdminEventHandler.php
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
use FireflyIII\Events\AdminRequestedTestMessage;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Mail\AdminTestMail;
use FireflyIII\Notifications\Admin\TestNotification;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Log;
use Mail;
use Session;

/**
 * Class AdminEventHandler.
 */
class AdminEventHandler
{
    /**
     * Sends a test message to an administrator.
     *
     * @param AdminRequestedTestMessage $event
     *
     * @return bool
     * @throws FireflyException
     * @deprecated
     */
    public function sendTestMessage(AdminRequestedTestMessage $event): bool
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        // do some validation.
        if (!$repository->hasRole($event->user, 'owner')) {
            return true;
        }

        Notification::send($event->user, new TestNotification($event->user->email));

        return true;
    }
}
