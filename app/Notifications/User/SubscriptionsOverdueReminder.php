<?php

declare(strict_types=1);

namespace FireflyIII\Notifications\User;

use Carbon\Carbon;
use FireflyIII\Notifications\ReturnsAvailableChannels;
use FireflyIII\Notifications\ReturnsSettings;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Pushover\PushoverMessage;

class SubscriptionsOverdueReminder extends Notification
{
    use Queueable;

    public function __construct(private array $overdue) {}

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toArray(User $notifiable): array
    {
        return [
        ];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toMail(User $notifiable): MailMessage
    {
        // format the data
        $info  = [];
        $count = 0;
        foreach ($this->overdue as $item) {
            $current              = [
                'bill' => $item['bill'],
            ];
            $current['pay_dates'] = array_map(
                static function (string $date): string {
                    return new Carbon($date)->isoFormat((string)trans('config.month_and_day_moment_js'));
                },
                $item['dates']['pay_dates']
            );
            $info[]               = $current;
            ++$count;
        }

        return new MailMessage()
            ->markdown('emails.subscriptions-overdue-warning', ['info' => $info, 'count' => $count])
            ->subject($this->getSubject())
        ;
    }

    private function getSubject(): string
    {
        if (count($this->overdue) > 1) {
            return (string)trans('email.subscriptions_overdue_subject_multi', ['count' => count($this->overdue)]);
        }

        return (string)trans('email.subscriptions_overdue_subject_single');
    }

    public function toNtfy(User $notifiable): Message
    {
        $settings = ReturnsSettings::getSettings('ntfy', 'user', $notifiable);
        $message  = new Message();
        $message->topic($settings['ntfy_topic']);
        $message->title($this->getSubject());
        $message->body((string)trans('email.bill_warning_please_action'));

        return $message;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toPushover(User $notifiable): PushoverMessage
    {
        return PushoverMessage::create((string)trans('email.bill_warning_please_action'))
            ->title($this->getSubject())
        ;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toSlack(User $notifiable): SlackMessage
    {
        $url = route('bills.index');

        return new SlackMessage()
            ->warning()
            ->attachment(static function ($attachment) use ($url): void {
                $attachment->title((string)trans('firefly.visit_bills'), $url);
            })
            ->content($this->getSubject())
        ;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function via(User $notifiable): array
    {
        return ReturnsAvailableChannels::returnChannels('user', $notifiable);
    }
}
