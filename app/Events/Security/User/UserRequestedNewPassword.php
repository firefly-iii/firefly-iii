<?php




declare(strict_types=1);



namespace FireflyIII\Events\Security\User;

use FireflyIII\Events\Event;
use FireflyIII\User;
use Illuminate\Queue\SerializesModels;
use SensitiveParameter;

class UserRequestedNewPassword extends Event
{
    use SerializesModels;

    public function __construct(
        public User $user,
        #[SensitiveParameter]
        public string $token,
        public string $ipAddress
    ) {}
}
