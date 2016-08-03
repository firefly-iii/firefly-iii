<?php
/**
 * UserSaveIpAddress.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\UserIsConfirmed;
use FireflyIII\Events\UserRegistration;
use FireflyIII\User;
use Preferences;

/**
 * Class UserSaveIpAddress
 *
 * @package FireflyIII\Handlers\Events
 */
class UserSaveIpAddress
{
    /**
     * Create the event listener.
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserRegistration $event
     *
     * @return bool
     */
    public function saveFromRegistration(UserRegistration $event): bool
    {
        Preferences::setForUser($event->user, 'registration_ip_address', $event->ipAddress);

        return true;
    }

    /**
     * Handle the event.
     *
     * @param  UserIsConfirmed $event
     *
     * @return bool
     */
    public function saveFromConfirmation(UserIsConfirmed $event): bool
    {
        Preferences::setForUser($event->user, 'confirmation_ip_address', $event->ipAddress);

        return true;
    }
}