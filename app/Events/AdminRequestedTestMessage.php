<?php
/**
 * AdminRequestedTestMessage.php
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
use Log;

/**
 * Class AdminRequestedTestMessage.
 */
class AdminRequestedTestMessage extends Event
{
    use SerializesModels;

    public $ipAddress;
    public $user;

    /**
     * Create a new event instance.
     *
     * @param User   $user
     * @param string $ipAddress
     */
    public function __construct(User $user, string $ipAddress)
    {
        Log::debug(sprintf('Triggered AdminRequestedTestMessage for user #%d (%s) and IP %s!', $user->id, $user->email, $ipAddress));
        $this->user      = $user;
        $this->ipAddress = $ipAddress;
    }
}
