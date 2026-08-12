<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\Subscription;

use FireflyIII\Events\Model\Subscription\SubscriptionNeedsExtensionOrRenewal;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\BillReminder;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesAboutExtensionOrRenewal implements ShouldQueue
{
    public function handle(SubscriptionNeedsExtensionOrRenewal $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $subscription = $event->subscription;

        /** @var bool $preference */
        $preference   = Preferences::getForUser($subscription->user, 'notification_bill_reminder', true)->data;

        if (true === $preference) {
            Log::debug('Subscription reminder is true!');
            NotificationSender::send($subscription->user, new BillReminder($subscription, $event->field, $event->diff));

            return;
        }
        Log::debug('User has disabled subscription reminders.');
    }
}
