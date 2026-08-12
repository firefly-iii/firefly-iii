<?php




declare(strict_types=1);


namespace FireflyIII\Listeners\Model\Subscription;

use FireflyIII\Events\Model\Subscription\SubscriptionsAreOverdueForPayment;
use FireflyIII\Models\Bill;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Notifications\User\SubscriptionsOverdueReminder;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifiesAboutOverdueSubscriptions implements ShouldQueue
{
    public function handle(SubscriptionsAreOverdueForPayment $event): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        // make sure user does not get the warning twice.
        $overdue          = $event->overdue;
        $user             = $event->user;
        $toBeWarned       = [];
        Log::debug(sprintf('%d subscriptions to warn about.', count($overdue)));
        foreach ($overdue as $item) {
            /** @var Bill $bill */
            $bill         = $item['bill'];
            $key          = sprintf('bill_overdue_%s_%s', $bill->id, substr(hash('sha256', json_encode($item['dates']['pay_dates'], JSON_THROW_ON_ERROR)), 0, 10));
            $pref         = Preferences::getForUser($bill->user, $key, false);
            if (true === $pref->data) {
                Log::debug(sprintf('User #%d has already been warned about overdue subscription #%d.', $bill->user->id, $bill->id));

                continue;
            }
            $toBeWarned[] = $item;
        }
        unset($bill);
        Log::debug(sprintf('Now %d subscription(s) to warn about.', count($toBeWarned)));

        /** @var bool $sendNotification */
        $sendNotification = Preferences::getForUser($user, 'notification_bill_reminder', true)->data;
        if (false === $sendNotification) {
            Log::debug('User has disabled subscription reminders.');

            return;
        }
        Log::debug(sprintf('Will warn about %d overdue subscription(s).', count($toBeWarned)));
        if (0 === count($toBeWarned)) {
            Log::debug('No overdue subscriptions to warn about.');

            return;
        }
        unset($item);
        foreach ($toBeWarned as $item) {
            /** @var Bill $bill */
            $bill = $item['bill'];
            $key  = sprintf('bill_overdue_%s_%s', $bill->id, substr(hash('sha256', json_encode($item['dates']['pay_dates'], JSON_THROW_ON_ERROR)), 0, 10));
            Preferences::setForUser($bill->user, $key, true);
        }
        Log::warning('should hit this ONCE');
        NotificationSender::send($user, new SubscriptionsOverdueReminder($toBeWarned));
    }
}
