<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\System;

use FireflyIII\Events\Security\System\UnknownUserTriedLogin;
use FireflyIII\Notifications\Admin\UnknownUserLoginAttempt;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\NotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifiesOwnerAboutUnknownUser implements ShouldQueue
{
    public function handle(UnknownUserTriedLogin $event): void
    {
        $owner = new OwnerNotifiable();
        NotificationSender::send($owner, new UnknownUserLoginAttempt($event->address));
    }
}
