<?php

/*
 * WebhookEventHandler.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Events;

use FireflyIII\Jobs\SendWebhookMessage;
use FireflyIII\Models\WebhookMessage;

/**
 * Class WebhookEventHandler
 */
class WebhookEventHandler
{
    /**
     * Will try to send at most 3 messages so the flow doesn't get broken too much.
     */
    public function sendWebhookMessages(): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        // kick off the job!
        $messages = WebhookMessage::where('webhook_messages.sent', false)
            ->get(['webhook_messages.*'])
            ->filter(
                static fn (WebhookMessage $message) => $message->webhookAttempts()->count() <= 2
            )->splice(0, 5)
        ;
        app('log')->debug(sprintf('Found %d webhook message(s) ready to be send.', $messages->count()));
        foreach ($messages as $message) {
            if (false === $message->sent) {
                app('log')->debug(sprintf('Send message #%d', $message->id));
                SendWebhookMessage::dispatch($message)->afterResponse();
            }
            if (false !== $message->sent) {
                app('log')->debug(sprintf('Skip message #%d', $message->id));
            }
        }
    }
}
