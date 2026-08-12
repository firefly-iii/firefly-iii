<?php




declare(strict_types=1);

namespace FireflyIII\Events\Security\User;

use FireflyIII\Events\Event;
use FireflyIII\User;
use Illuminate\Queue\SerializesModels;

class UserLoggedInFromNewIpAddress extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance. This event is triggered when a new user registers.
     */
    public function __construct(
        public User $user
    ) {}
}
