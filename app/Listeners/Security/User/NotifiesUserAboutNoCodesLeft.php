<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserHasNoMFABackupCodesLeft;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\MFABackupNoLeftNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesUserAboutNoCodesLeft implements ShouldQueue
{
    public function handle(UserHasNoMFABackupCodesLeft $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;

        NotificationSender::send($user, new MFABackupNoLeftNotification($user));
    }
}
