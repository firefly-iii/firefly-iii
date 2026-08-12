<?php




declare(strict_types=1);



namespace FireflyIII\Events\Security\User;

use FireflyIII\Events\Event;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

class UserFailedLoginAttempt extends Event
{
    use SerializesModels;

    public User $user;

    public function __construct(Authenticatable|User|null $user)
    {
        if ($user instanceof User) {
            $this->user = $user;

            return;
        }

        throw new InvalidArgumentException(sprintf('User cannot be an instance of %s.', get_class($user)));
    }
}
