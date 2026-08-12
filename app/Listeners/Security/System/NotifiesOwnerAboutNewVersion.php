<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\System;

use FireflyIII\Events\Security\System\SystemFoundNewVersionOnline;
use FireflyIII\Notifications\Admin\VersionCheckResult;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Support\Facades\AppConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifiesOwnerAboutNewVersion implements ShouldQueue
{
    public function handle(SystemFoundNewVersionOnline $event): void
    {
        $sendMail = AppConfiguration::get('notification_new_version', true)->data;
        if (false === $sendMail) {
            return;
        }

        $owner    = new OwnerNotifiable();
        NotificationSender::send($owner, new VersionCheckResult($event->message));
    }
}
