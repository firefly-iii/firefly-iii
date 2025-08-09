<?php

declare(strict_types=1);

namespace FireflyIII\Notifications\User;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Notifications\ReturnsAvailableChannels;
use FireflyIII\Notifications\ReturnsSettings;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Pushover\PushoverMessage;

class SubscriptionOverdueReminder extends Notification
{
    use Queueable;

    public function __construct(private Bill $bill, private array $dates) {}

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
        // format the dates in a human-readable way
        $this->dates['pay_dates'] = array_map(
            static function (string $date): string {
                return new Carbon($date)->isoFormat((string) trans('config.month_and_day_moment_js'));
            },
            $this->dates['pay_dates']
        );

        return new MailMessage()
            ->markdown('emails.subscription-overdue-warning', ['bill' => $this->bill, 'dates' => $this->dates])
            ->subject($this->getSubject())
        ;
    }

    private function getSubject(): string
    {
        return (string) trans('email.subscription_overdue_subject', ['name' => $this->bill->name]);
    }

    public function toNtfy(User $notifiable): Message
    {
        $settings = ReturnsSettings::getSettings('ntfy', 'user', $notifiable);
        $message  = new Message();
        $message->topic($settings['ntfy_topic']);
        $message->title($this->getSubject());
        $message->body((string) trans('email.bill_warning_please_action'));

        return $message;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toPushover(User $notifiable): PushoverMessage
    {
        return PushoverMessage::create((string) trans('email.bill_warning_please_action'))
            ->title($this->getSubject())
        ;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toSlack(User $notifiable): SlackMessage
    {
        $bill = $this->bill;
        $url  = route('bills.show', [$bill->id]);

        return new SlackMessage()
            ->warning()
            ->attachment(static function ($attachment) use ($bill, $url): void {
                $attachment->title((string) trans('firefly.visit_bill', ['name' => $bill->name]), $url);
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
