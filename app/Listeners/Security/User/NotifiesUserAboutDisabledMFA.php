<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserHasDisabledMFA;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\DisabledMFANotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesUserAboutDisabledMFA implements ShouldQueue
{
    public function handle(UserHasDisabledMFA $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        NotificationSender::send($event->user, new DisabledMFANotification($event->user));
    }
}
