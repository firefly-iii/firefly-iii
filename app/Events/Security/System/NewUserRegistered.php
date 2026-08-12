<?php




declare(strict_types=1);



namespace FireflyIII\Events\Security\System;

use FireflyIII\Events\Event;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\User;
use Illuminate\Queue\SerializesModels;

class NewUserRegistered extends Event
{
    use SerializesModels;

    public function __construct(
        public OwnerNotifiable $owner,
        public User $user
    ) {}
}
