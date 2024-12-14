<?php

/*
 * UserLogin.php
 * Copyright (c) 2022 james@firefly-iii.org
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Notifications\ReturnsAvailableChannels;
use FireflyIII\Notifications\ReturnsSettings;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Pushover\PushoverMessage;
use Ntfy\Message;

/**
 * Class UserLogin
 */
class UserLogin extends Notification
{
    use Queueable;

    private string $ip;

    public function __construct(string $ip)
    {
        $this->ip = $ip;
    }

    public function toArray(User $notifiable)
    {
        return [
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toMail(User $notifiable)
    {
        $time = now(config('app.timezone'))->isoFormat((string) trans('config.date_time_js'));

        return (new MailMessage())
            ->markdown('emails.new-ip', ['time' => $time, 'ipAddress' => $this->ip, 'host' => $this->getHost()])
            ->subject((string) trans('email.login_from_new_ip'))
        ;
    }

    public function toNtfy(User $notifiable): Message
    {
        $settings = ReturnsSettings::getSettings('ntfy', 'user', $notifiable);
        $message  = new Message();
        $message->topic($settings['ntfy_topic']);
        $message->title((string) trans('email.login_from_new_ip'));
        $message->body((string) trans('email.slack_login_from_new_ip', ['host' => $this->getHost(), 'ip' => $this->ip]));

        return $message;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toPushover(User $notifiable): PushoverMessage
    {
        return PushoverMessage::create((string) trans('email.slack_login_from_new_ip', ['host' => $this->getHost(), 'ip' => $this->ip]))
            ->title((string) trans('email.login_from_new_ip'))
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toSlack(User $notifiable)
    {
        return new SlackMessage()->content((string) trans('email.slack_login_from_new_ip', ['host' => $this->getHost(), 'ip' => $this->ip]));
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function via(User $notifiable)
    {
        return ReturnsAvailableChannels::returnChannels('user', $notifiable);
    }

    private function getHost(): string
    {
        $host = '';

        try {
            $hostName = app('steam')->getHostName($this->ip);
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            $hostName = $this->ip;
        }
        if ($hostName !== $this->ip) {
            $host = $hostName;
        }

        return $host;
    }
}
