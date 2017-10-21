<?php
/**
 * UserChangedEmail.php
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
 * Class UserChangedEmail
 *
 * @package FireflyIII\Events
 */
class UserChangedEmail extends Event
{
    use SerializesModels;

    /** @var  string */
    public $ipAddress;
    /** @var  string */
    public $newEmail;
    /** @var  string */
    public $oldEmail;
    /** @var User */
    public $user;

    /**
     * UserChangedEmail constructor.
     *
     * @param User   $user
     * @param string $newEmail
     * @param string $oldEmail
     * @param string $ipAddress
     */
    public function __construct(User $user, string $newEmail, string $oldEmail, string $ipAddress)
    {
        $this->user      = $user;
        $this->ipAddress = $ipAddress;
        $this->oldEmail  = $oldEmail;
        $this->newEmail  = $newEmail;
    }
}