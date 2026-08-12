<?php




declare(strict_types=1);



namespace FireflyIII\Events\Security\User;

use FireflyIII\Events\Event;
use FireflyIII\User;
use Illuminate\Queue\SerializesModels;

class UserChangedEmailAddress extends Event
{
    use SerializesModels;

    /**
     * UserChangedEmail constructor.
     */
    public function __construct(
        public User $user,
        public string $newEmail,
        public string $oldEmail
    ) {}
}
