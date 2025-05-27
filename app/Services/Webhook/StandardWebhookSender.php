<?php

/*
 * StandardWebhookSender.php
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

namespace FireflyIII\Services\Webhook;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Webhook\SignatureGeneratorInterface;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use JsonException;

use function Safe\json_encode;

/**
 * Class StandardWebhookSender
 */
class StandardWebhookSender implements WebhookSenderInterface
{
    private WebhookMessage $message;
    private int            $version = 1;

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @throws GuzzleException
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function send(): void
    {
        // have the signature generator generate a signature. If it fails, the error thrown will
        // end up in send() to be caught.
        $signatureGenerator  = app(SignatureGeneratorInterface::class);
        $this->message->sent = true;
        $this->message->save();

        try {
            $signature = $signatureGenerator->generate($this->message);
        } catch (FireflyException $e) {
            app('log')->error('Did not send message because of a Firefly III Exception.');
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $attempt                = new WebhookAttempt();
            $attempt->webhookMessage()->associate($this->message);
            $attempt->status_code   = 0;
            $attempt->logs          = sprintf('Exception: %s', $e->getMessage());
            $attempt->save();
            $this->message->errored = true;
            $this->message->sent    = false;
            $this->message->save();

            return;
        }

        app('log')->debug(sprintf('Trying to send webhook message #%d', $this->message->id));

        try {
            $json = json_encode($this->message->message, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            app('log')->error('Did not send message because of a JSON error.');
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());
            $attempt                = new WebhookAttempt();
            $attempt->webhookMessage()->associate($this->message);
            $attempt->status_code   = 0;
            $attempt->logs          = sprintf('Json error: %s', $e->getMessage());
            $attempt->save();
            $this->message->errored = true;
            $this->message->sent    = false;
            $this->message->save();

            return;
        }
        $options             = [
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
        $client              = new Client();

        try {
            $res = $client->request('POST', $this->message->webhook->url, $options);
        } catch (ConnectException|RequestException $e) {
            app('log')->error('The webhook could NOT be submitted but Firefly III caught the error below.');
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

            $logs                   = sprintf("%s\n%s", $e->getMessage(), $e->getTraceAsString());

            $this->message->errored = true;
            $this->message->sent    = false;
            $this->message->save();

            $attempt                = new WebhookAttempt();
            $attempt->webhookMessage()->associate($this->message);
            $attempt->status_code   = 0;
            if (method_exists($e, 'hasResponse') && method_exists($e, 'getResponse')) {
                $attempt->status_code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                app('log')->error(sprintf('The status code of the error response is: %d', $attempt->status_code));
                $body                 = (string) ($e->hasResponse() ? $e->getResponse()->getBody() : '');
                app('log')->error(sprintf('The body of the error response is: %s', $body));
            }
            $attempt->logs          = $logs;
            $attempt->save();

            return;
        }
        $this->message->sent = true;
        $this->message->save();

        app('log')->debug(sprintf('Webhook message #%d was sent. Status code %d', $this->message->id, $res->getStatusCode()));
        app('log')->debug(sprintf('Webhook request body size: %d bytes', strlen($json)));
        app('log')->debug(sprintf('Response body: %s', $res->getBody()));
    }

    public function setMessage(WebhookMessage $message): void
    {
        $this->message = $message;
    }
}
