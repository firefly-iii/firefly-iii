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
        $messages = WebhookMessage::where('sent', 0)
                                  ->where('attempts', '<=', $max)
                                  ->get();
        Log::debug(sprintf('Going to send %d webhook message(s)', $messages->count()));
        /** @var WebhookMessage $message */
        foreach ($messages as $message) {
            $this->sendMessage($message);
        }
    }

    /**
     * @param WebhookMessage $message
     */
    private function sendMessage(WebhookMessage $message): void
    {
        Log::debug(sprintf('Trying to send webhook message #%d', $message->id));
        try {
            $json = json_encode($message->message, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $message->attempts++;
            $message->logs[] = sprintf('%s: %s', date('Y-m-d H:i:s'), sprintf('Json error: %s', $e->getMessage()));
            $message->save();

            return;
        }
        $user = $message->webhook->user;
        try {
            $token = $user->generateAccessToken();
        } catch (Exception $e) {
            $message->attempts++;
            $message->logs[] = sprintf('%s: %s', date('Y-m-d H:i:s'), sprintf('Could not generate token: %s', $e->getMessage()));
            $message->save();

            return;
        }
        $accessToken = app('preferences')->getForUser($user, 'access_token', $token);
        $signature   = hash_hmac('sha3-256', $json, $accessToken->data, false);
        $options     = [
            'body'    => $json,
            'headers' => [
                'Content-Type'    => 'application/json',
                'Accept'          => 'application/json',
                'Signature'       => $signature,
                'connect_timeout' => 3.14,
                'User-Agent'      => sprintf('FireflyIII/%s', config('firefly.version')),
                'timeout'         => 10,
            ],
        ];
        $client      = new Client;
        $logs        = $message->logs ?? [];
        try {
            $res           = $client->request('GET', $message->webhook->url, $options);
            $message->sent = true;
        } catch (ClientException|Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $logs[]           = sprintf('%s: %s', date('Y-m-d H:i:s'), $e->getMessage());
            $message->errored = true;
            $message->sent    = false;
        }
        $message->attempts++;
        $message->logs = $logs;
        $message->save();

        Log::debug(sprintf('Webhook message #%d was sent. Status code %d', $message->id, $res->getStatusCode()));
        Log::debug(sprintf('Response body: %s', $res->getBody()));
    }

}