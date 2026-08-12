<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class RespondsToNewLogin implements ShouldQueue
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        if (!$user instanceof User) {
            throw new InvalidArgumentException(sprintf('User cannot be an instance of %s.', get_class($user)));
        }

        $this->checkSingleUserIsAdmin($user);
        $this->demoUserBackToEnglish($user);
    }

    /**
     * Fires to see if a user is admin.
     */
    private function checkSingleUserIsAdmin(User $user): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $count      = $repository->count();

        // only act when there is 1 user in the system, and he has no admin rights.
        if (1 === $count && !$repository->hasRole($user, 'owner')) {
            // user is the only user but does not have role "owner".
            $role = $repository->getRole('owner');
            if (null === $role) {
                // create role, does not exist. Very strange situation so let's raise a big fuss about it.
                $role = $repository->createRole('owner', 'Site Owner', 'User runs this instance of FF3');
                Log::error('Could not find role "owner". This is weird.');
            }

            Log::info(sprintf('Gave user #%d role #%d ("%s")', $user->id, $role->id, $role->name));
            // give user the role
            $repository->attachRole($user, 'owner');
        }
    }

    /**
     * Set the demo user back to English.
     */
    private function demoUserBackToEnglish(User $user): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        if ($repository->hasRole($user, 'demo')) {
            // set user back to English.
            Preferences::setForUser($user, 'language', 'en_US');
            Preferences::setForUser($user, 'locale', 'equal');
            Preferences::setForUser($user, 'anonymous', false);
            Preferences::mark();
        }
    }
}
