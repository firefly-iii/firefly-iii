<?php
/**
 * DeletedUser.php
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
 * Class DeletedUser
 *
 * @package FireflyIII\Events
 */
class DeletedUser extends Event
{
    use SerializesModels;

    public $email;

    /**
     * Create a new event instance. This event is triggered when a user deletes themselves.
     *
     * @param  string $email
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }
}