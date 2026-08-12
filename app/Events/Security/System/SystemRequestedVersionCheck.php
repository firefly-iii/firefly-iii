<?php




declare(strict_types=1);



namespace FireflyIII\Events\Security\System;

use FireflyIII\Events\Event;
use FireflyIII\User;
use Illuminate\Queue\SerializesModels;

class SystemRequestedVersionCheck extends Event
{
    use SerializesModels;

    public function __construct(
        public User $user
    ) {}
}
