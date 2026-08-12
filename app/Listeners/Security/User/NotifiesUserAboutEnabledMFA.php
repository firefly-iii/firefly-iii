<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserHasEnabledMFA;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\EnabledMFANotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesUserAboutEnabledMFA implements ShouldQueue
{
    public function handle(UserHasEnabledMFA $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;
        NotificationSender::send($user, new EnabledMFANotification($user));
    }
}
