<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\NewAccessToken;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Laravel\Passport\Events\AccessTokenCreated;

class NotifiesUserAboutNewAccessToken
{
    public function handle(AccessTokenCreated $event): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->find((int) $event->userId);

        if (null !== $user) {
            NotificationSender::send($user, new NewAccessToken());
        }
    }
}
