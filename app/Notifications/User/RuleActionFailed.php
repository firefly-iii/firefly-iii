<?php

/*
 * NewAccessToken.php
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

use FireflyIII\Support\Notifications\UrlValidator;
use FireflyIII\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

/**
 * Class RuleActionFailed
 */
class RuleActionFailed extends Notification
{
    use Queueable;

    private string $groupLink;
    private string $groupTitle;
    private string $message;
    private string $ruleLink;
    private string $ruleTitle;


    public function __construct(array $params)
    {
        [$mainMessage, $groupTitle, $groupLink, $ruleTitle, $ruleLink] = $params;
        $this->message                                                 = $mainMessage;
        $this->groupTitle                                              = $groupTitle;
        $this->groupLink                                               = $groupLink;
        $this->ruleTitle                                               = $ruleTitle;
        $this->ruleLink                                                = $ruleLink;
    }


    public function toArray($notifiable)
    {
        return [
        ];
    }


    public function toSlack($notifiable)
    {
        $groupTitle = $this->groupTitle;
        $groupLink  = $this->groupLink;
        $ruleTitle  = $this->ruleTitle;
        $ruleLink   = $this->ruleLink;

        return (new SlackMessage())->content($this->message)->attachment(static function ($attachment) use ($groupTitle, $groupLink): void {
            $attachment->title((string)trans('rules.inspect_transaction', ['title' => $groupTitle]), $groupLink);
        })->attachment(static function ($attachment) use ($ruleTitle, $ruleLink): void {
            $attachment->title((string)trans('rules.inspect_rule', ['title' => $ruleTitle]), $ruleLink);
        });
    }


    public function via($notifiable)
    {
        /** @var null|User $user */
        $user     = auth()->user();
        $slackUrl = null === $user ? '' : app('preferences')->getForUser(auth()->user(), 'slack_webhook_url', '')->data;
        if (is_array($slackUrl)) {
            $slackUrl = '';
        }
        if (UrlValidator::isValidWebhookURL((string)$slackUrl)) {
            app('log')->debug('Will send ruleActionFailed through Slack or Discord!');

            return ['slack'];
        }
        app('log')->debug('Will NOT send ruleActionFailed through Slack or Discord');

        return [];
    }
}
