<?php
/**
 * RequestedNewPassword.php
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
     * @param string $ipAddress
     */
    public function __construct(User $user, string $token, string $ipAddress)
    {
        $this->user      = $user;
        $this->token     = $token;
        $this->ipAddress = $ipAddress;
    }
}
