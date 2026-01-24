<?php
/*
 * SendsWebhookMessages.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Listeners\Model\Webhook;

use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Jobs\SendWebhookMessage;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Support\Facades\Log;

class SendsWebhookMessages
{
    public function handle(WebhookMessagesRequestSending $event): void {
        Log::debug(sprintf('Now in %s for %s', __METHOD__, get_class($event)));
        if (false === config('firefly.feature_flags.webhooks') || false === FireflyConfig::get('allow_webhooks', config('firefly.allow_webhooks'))->data) {
            Log::debug('Webhook event handler is disabled, do not run sendWebhookMessages().');

            return;
        }

        // kick off the job!
        $messages = WebhookMessage::where('webhook_messages.sent', false)
                                  ->get(['webhook_messages.*'])
                                  ->filter(static fn (WebhookMessage $message): bool => $message->webhookAttempts()->count() <= 2)
                                  ->splice(0, 5)
        ;
        Log::debug(sprintf('Found %d webhook message(s) ready to be send.', $messages->count()));

        /** @var WebhookMessage $message */
        foreach ($messages as $message) {
            if (false === $message->sent) {
                // set it to "sent" right away!
                $message->sent = true;
                $message->save();
                Log::debug(sprintf('Send message #%d', $message->id));
                SendWebhookMessage::dispatch($message)->afterResponse();
            }
            if (false !== $message->sent) {
                Log::debug(sprintf('Skip message #%d', $message->id));
            }
        }

        // clean up sent messages table:
        WebhookMessage::where('webhook_messages.sent', true)->where('webhook_messages.created_at', '<', now()->subDays(14))->delete();
    }

}
