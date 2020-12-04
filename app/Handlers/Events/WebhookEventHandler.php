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
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
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
     *
     */
    public function sendWebhookMessages(): void
    {
        $max      = (int)config('firefly.webhooks.max_attempts');
        $max      = 0 === $max ? 3 : $max;
        $messages = WebhookMessage
            ::where('webhook_messages.sent', 0)
            ->where('webhook_messages.errored', 0)
            ->get(['webhook_messages.*']);
        Log::debug(sprintf('Found %d webhook message(s) to be send.', $messages->count()));
        /** @var WebhookMessage $message */
        foreach ($messages as $message) {
            $count = $message->webhookAttempts()->count();
            if ($count >= 3) {
                Log::info('No send message.');
                continue;
            }
            // TODO needs its own handler.
            $this->sendMessageV0($message);
        }
    }

    /**
     * @param WebhookMessage $message
     */
    private function sendMessageV0(WebhookMessage $message): void
    {
        Log::debug(sprintf('Trying to send webhook message #%d', $message->id));
        try {
            $json = json_encode($message->message, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $attempt = new WebhookAttempt;
            $attempt->webhookMessage()->associate($message);
            $attempt->status_code = 0;
            $attempt->logs        = sprintf('Json error: %s', $e->getMessage());
            $attempt->save();

            return;
        }
        // signature v0 is generated using the following structure:
        // The signed_payload string is created by concatenating:
        // The timestamp (as a string)
        // The character .
        // The character .
        // The actual JSON payload (i.e., the request body)
        $timestamp = time();
        $payload   = sprintf('%s.%s', $timestamp, $json);
        $signature = hash_hmac('sha3-256', $payload, $message->webhook->secret, false);

        // signature string:
        // header included in each signed event contains a timestamp and one or more signatures.
        // The timestamp is prefixed by t=, and each signature is prefixed by a scheme.
        // Schemes start with v, followed by an integer. Currently, the only valid live signature scheme is v0.
        $signatureString = sprintf('t=%s,v0=%s', $timestamp, $signature);

        $options = [
            'body'    => $json,
            'headers' => [
                'Content-Type'    => 'application/json',
                'Accept'          => 'application/json',
                'Signature'       => $signatureString,
                'connect_timeout' => 3.14,
                'User-Agent'      => sprintf('FireflyIII/%s', config('firefly.version')),
                'timeout'         => 10,
            ],
        ];
        $client  = new Client;
        $logs    = $message->logs ?? [];
        try {
            $res           = $client->request('POST', $message->webhook->url, $options);
            $message->sent = true;
        } catch (ClientException|Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $logs[]           = sprintf('%s: %s', date('Y-m-d H:i:s'), $e->getMessage());
            $message->errored = true;
            $message->sent    = false;
        }
        $message->save();

        $attempt = new WebhookAttempt;
        $attempt->webhookMessage()->associate($message);
        $attempt->status_code = $res->getStatusCode();
        $attempt->logs        = '';
        $attempt->response    = (string)$res->getBody();
        $attempt->save();

        Log::debug(sprintf('Webhook message #%d was sent. Status code %d', $message->id, $res->getStatusCode()));
        Log::debug(sprintf('Webhook request body size: %d bytes', strlen($json)));
        Log::debug(sprintf('Response body: %s', $res->getBody()));
    }

}