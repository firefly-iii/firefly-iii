<?php
/**
 * BlockedUseOfDomain.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Events;


use Illuminate\Queue\SerializesModels;

/**
 * Class BlockedUseOfDomain
 *
 * @package FireflyIII\Events
 */
class BlockedUseOfDomain extends Event
{
    use SerializesModels;

    public $email;
    public $ipAddress;

    /**
     * Create a new event instance. This event is triggered when a user tries to register with a banned domain (on blocked domain list).
     *
     * @param string $email
     * @param string $ipAddress
     */
    public function __construct(string $email, string $ipAddress)
    {
        $this->email     = $email;
        $this->ipAddress = $ipAddress;
    }
}
