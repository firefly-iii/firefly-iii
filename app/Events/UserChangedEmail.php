<?php
/**
 * UserChangedEmail.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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