<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Test;

use FireflyIII\Events\Test\OwnerTestsNotificationChannel;
use FireflyIII\Events\Test\UserTestsNotificationChannel;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\Test\OwnerTestNotificationEmail;
use FireflyIII\Notifications\Test\OwnerTestNotificationPushover;
use FireflyIII\Notifications\Test\OwnerTestNotificationSlack;
use FireflyIII\Notifications\Test\UserTestNotificationEmail;
use FireflyIII\Notifications\Test\UserTestNotificationPushover;
use FireflyIII\Notifications\Test\UserTestNotificationSlack;
use Illuminate\Support\Facades\Log;

class SendsTestNotification
{
    public function handle(OwnerTestsNotificationChannel|UserTestsNotificationChannel $event): void
    {
        Log::debug(sprintf('Now in SendsTestNotification::handle(%s->"%s")', get_class($event), $event->channel));

        $type = str_contains(get_class($event), 'Owner') ? 'owner' : 'user';
        $key  = sprintf('%s-%s', $type, $event->channel);

        switch ($key) {
            case 'user-email':
                $class = UserTestNotificationEmail::class;

                break;

            case 'user-slack':
                $class = UserTestNotificationSlack::class;

                break;

            case 'user-pushover':
                $class = UserTestNotificationPushover::class;

                break;

            case 'owner-email':
                $class = OwnerTestNotificationEmail::class;

                break;

            case 'owner-slack':
                $class = OwnerTestNotificationSlack::class;

                break;

            case 'owner-pushover':
                $class = OwnerTestNotificationPushover::class;

                break;

            default:
                Log::error(sprintf('Unknown key "%s" in sendTestNotification method.', $key));

                return;
        }
        Log::debug(sprintf('Will send %s as a notification.', $class));
        NotificationSender::send($event->{$type}, new $class());
        Log::debug(sprintf('If you see no errors above this line, test notification was sent over channel "%s"', $event->channel));
    }
}
