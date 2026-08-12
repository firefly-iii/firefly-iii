<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserHasFewMFABackupCodesLeft;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\MFABackupFewLeftNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesUserAboutFewCodesLeft implements ShouldQueue
{
    public function handle(UserHasFewMFABackupCodesLeft $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        $user  = $event->user;
        $count = $event->count;
        NotificationSender::send($user, new MFABackupFewLeftNotification($user, $count));
    }
}
