<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserFailedLoginAttempt;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\UserFailedLoginAttempt as NotificationFailedLoginAttempt;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifiesUserAboutFailedLogin implements ShouldQueue
{
    public function handle(UserFailedLoginAttempt $event): void
    {
        NotificationSender::send($event->user, new NotificationFailedLoginAttempt($event->user));
    }
}
