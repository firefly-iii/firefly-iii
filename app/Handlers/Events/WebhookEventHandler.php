<?php
/*
 * WebhookEventHandler.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Handlers\Events;


use Exception;
use FireflyIII\Helpers\Webhook\SignatureGeneratorInterface;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Services\Webhook\WebhookSenderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use JsonException;
use Log;

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
        $messages = WebhookMessage
            ::where('webhook_messages.sent', 0)
            ->where('webhook_messages.errored', 0)
            ->get(['webhook_messages.*'])
            ->filter(
                function (WebhookMessage $message) {
                    return $message->webhookAttempts()->count() <= 2;
                }
            )->splice(0, 3);
        Log::debug(sprintf('Found %d webhook message(s) ready to be send.', $messages->count()));

        $sender =app(WebhookSenderInterface::class);
        $sender->setMessages($messages);
        $sender->send();

    }
}