<?php

/*
 * ReturnsAvailableChannels.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Notifications;

use FireflyIII\Support\Notifications\UrlValidator;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Pushover\PushoverChannel;
use Wijourdil\NtfyNotificationChannel\Channels\NtfyChannel;

class ReturnsAvailableChannels
{
    public static function returnChannels(string $type, ?User $user = null): array
    {
        $channels = ['mail'];

        if ('owner' === $type) {
            return self::returnOwnerChannels();
        }
        if ('user' === $type && null !== $user) {
            return self::returnUserChannels($user);
        }


        return $channels;
    }

    private static function returnOwnerChannels(): array
    {

        $channels = ['mail'];
        $slackUrl = app('fireflyconfig')->getEncrypted('slack_webhook_url', '')->data;
        if (UrlValidator::isValidWebhookURL($slackUrl)) {
            $channels[] = 'slack';
        }

        // validate presence of of Ntfy settings.
        if ('' !== (string) app('fireflyconfig')->getEncrypted('ntfy_topic', '')->data) {
            Log::debug('Enabled ntfy.');
            $channels[] = NtfyChannel::class;
        }
        if ('' === (string) app('fireflyconfig')->getEncrypted('ntfy_topic', '')->data) {
            Log::warning('No topic name for Ntfy, channel is disabled.');
        }

        // pushover
        $pushoverAppToken  = (string) app('fireflyconfig')->getEncrypted('pushover_app_token', '')->data;
        $pushoverUserToken = (string) app('fireflyconfig')->getEncrypted('pushover_user_token', '')->data;
        if ('' === $pushoverAppToken || '' === $pushoverUserToken) {
            Log::warning('[b] No Pushover token, channel is disabled.');
        }
        if ('' !== $pushoverAppToken && '' !== $pushoverUserToken) {
            Log::debug('Enabled pushover.');
            $channels[] = PushoverChannel::class;
        }

        Log::debug(sprintf('Final channel set in ReturnsAvailableChannels: %s ', implode(', ', $channels)));

        return $channels;
    }

    private static function returnUserChannels(User $user): array
    {
        $channels = ['mail'];
        $slackUrl = app('preferences')->getEncryptedForUser($user, 'slack_webhook_url', '')->data;
        if (UrlValidator::isValidWebhookURL($slackUrl)) {
            $channels[] = 'slack';
        }

        // validate presence of of Ntfy settings.
        if ('' !== (string) app('preferences')->getEncryptedForUser($user, 'ntfy_topic', '')->data) {
            Log::debug('Enabled ntfy.');
            $channels[] = NtfyChannel::class;
        }
        if ('' === (string) app('preferences')->getEncryptedForUser($user, 'ntfy_topic', '')->data) {
            Log::warning('No topic name for Ntfy, channel is disabled.');
        }

        // pushover
        $pushoverAppToken  = (string) app('preferences')->getEncryptedForUser($user, 'pushover_app_token', '')->data;
        $pushoverUserToken = (string) app('preferences')->getEncryptedForUser($user, 'pushover_user_token', '')->data;
        if ('' === $pushoverAppToken || '' === $pushoverUserToken) {
            Log::warning('[b] No Pushover token, channel is disabled.');
        }
        if ('' !== $pushoverAppToken && '' !== $pushoverUserToken) {
            Log::debug('Enabled pushover.');
            $channels[] = PushoverChannel::class;
        }

        Log::debug(sprintf('Final channel set in ReturnsAvailableChannels (user): %s ', implode(', ', $channels)));

        // only the owner can get notifications over
        return $channels;
    }
}
