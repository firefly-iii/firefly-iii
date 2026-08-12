<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserHasGeneratedNewBackupCodes;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\NewBackupCodesNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesUserAboutNewBackupCodes implements ShouldQueue
{
    public function handle(UserHasGeneratedNewBackupCodes $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $user = $event->user;
        NotificationSender::send($user, new NewBackupCodesNotification($user));
    }
}
