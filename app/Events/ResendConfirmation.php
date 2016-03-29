<?php
/**
 * ResendConfirmation.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Events;

use FireflyIII\User;
use Illuminate\Queue\SerializesModels;

/**
 * Class ResendConfirmation
 *
 * @package FireflyIII\Events
 */
class ResendConfirmation extends Event
{
    use SerializesModels;

    public $ipAddress;
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  User  $user
     * @param string $ipAddress
     */
    public function __construct(User $user, string $ipAddress)
    {
        $this->user      = $user;
        $this->ipAddress = $ipAddress;
    }
}
