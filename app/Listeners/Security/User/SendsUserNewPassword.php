<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserRequestedNewPassword;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\UserNewPassword;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendsUserNewPassword implements ShouldQueue
{
    public function handle(UserRequestedNewPassword $event): void
    {
        NotificationSender::send($event->user, new UserNewPassword(route('password.reset', [$event->token])));
    }
}
