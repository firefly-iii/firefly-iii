<?php


/*
 * SubscriptionsOverdueReminder.php
 * Copyright (c) 2025 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Notifications\User;

use Carbon\Carbon;
use FireflyIII\Notifications\ReturnsAvailableChannels;
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
                static fn (string $date): string => new Carbon($date)->isoFormat((string)trans('config.month_and_day_moment_js')),
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

    //    public function toNtfy(User $notifiable): Message
    //    {
    //        $settings = ReturnsSettings::getSettings('ntfy', 'user', $notifiable);
    //        $message  = new Message();
    //        $message->topic($settings['ntfy_topic']);
    //        $message->title($this->getSubject());
    //        $message->body((string)trans('email.bill_warning_please_action'));
    //
    //        return $message;
    //    }

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
