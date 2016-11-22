<?php
/**
 * RequestedNewPassword.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Events;

use FireflyIII\User;
use Illuminate\Queue\SerializesModels;

/**
 * Class RequestedNewPassword
 *
 * @package FireflyIII\Events
 */
class RequestedNewPassword extends Event
{
    use SerializesModels;

    public $ipAddress;
    public $token;
    public $user;

    /**
     * Create a new event instance. This event is triggered when a users tries to reset his or her password.
     *
     * @param  User  $user
     * @param string $token
     */
    public function __construct(User $user, string $token, string $ipAddress)
    {
        $this->user      = $user;
        $this->token     = $token;
        $this->ipAddress = $ipAddress;
    }

}