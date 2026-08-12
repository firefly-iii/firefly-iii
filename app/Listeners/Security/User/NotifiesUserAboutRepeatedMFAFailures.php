<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\User;

use FireflyIII\Events\Security\User\UserKeepsFailingMFA;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Security\MFAManyFailedAttemptsNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesUserAboutRepeatedMFAFailures implements ShouldQueue
{
    public function handle(UserKeepsFailingMFA $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        $user  = $event->user;
        $count = $event->count;
        NotificationSender::send($user, new MFAManyFailedAttemptsNotification($user, $count));
    }
}
