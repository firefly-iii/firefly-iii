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
/*
 * StandardWebhookSender.php
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

namespace FireflyIII\Services\Webhook;

use Exception;
use FireflyIII\Helpers\Webhook\SignatureGeneratorInterface;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use JsonException;
use Log;

/**
 * Class StandardWebhookSender
 */
class StandardWebhookSender implements WebhookSenderInterface
{
    private WebhookMessage $message;
    private int            $version = 1;

    /**
     * @inheritDoc
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @inheritDoc
     */
    public function setMessage(WebhookMessage $message): void
    {
        $this->message = $message;
    }

    /**
     * @inheritDoc
     */
    public function send(): void
    {
        // have the signature generator generate a signature. If it fails, the error thrown will
        // end up in send() to be caught.
        $signatureGenerator = app(SignatureGeneratorInterface::class);
        $signature          = $signatureGenerator->generate($this->message);

        Log::debug(sprintf('Trying to send webhook message #%d', $this->message->id));

        try {
            $json = json_encode($this->message->message, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error('Did not send message because of a JSON error.');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $attempt = new WebhookAttempt;
            $attempt->webhookMessage()->associate($this->message);
            $attempt->status_code = 0;
            $attempt->logs        = sprintf('Json error: %s', $e->getMessage());
            $attempt->save();
            $this->message->errored = true;
            $this->message->sent    = false;
            $this->message->save();

            return;
        }
        $options = [
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
        $client  = new Client;
        try {
            $res                 = $client->request('POST', $this->message->webhook->url, $options);
            $this->message->sent = true;
        } catch (ClientException | Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            //$logs[]           = sprintf('%s: %s', date('Y-m-d H:i:s'), $e->getMessage());
            $this->message->errored = true;
            $this->message->sent    = false;
            $this->message->save();
            return;
        }
        $this->message->save();

        Log::debug(sprintf('Webhook message #%d was sent. Status code %d', $this->message->id, $res->getStatusCode()));
        Log::debug(sprintf('Webhook request body size: %d bytes', strlen($json)));
        Log::debug(sprintf('Response body: %s', $res->getBody()));
    }
}
