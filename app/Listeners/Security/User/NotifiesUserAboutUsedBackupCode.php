<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserHasUsedBackupCode;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\MFAUsedBackupCodeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesUserAboutUsedBackupCode implements ShouldQueue
{
    public function handle(UserHasUsedBackupCode $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        $user = $event->user;
        NotificationSender::send($user, new MFAUsedBackupCodeNotification($user));
    }
}
