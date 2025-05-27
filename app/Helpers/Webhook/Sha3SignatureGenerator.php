<?php

/*
 * Sha3SignatureGenerator.php
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

namespace FireflyIII\Helpers\Webhook;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\WebhookMessage;
use JsonException;

use function Safe\json_encode;

/**
 * Class Sha3SignatureGenerator
 */
class Sha3SignatureGenerator implements SignatureGeneratorInterface
{
    private int $version = 1;

    /**
     * @throws FireflyException
     */
    public function generate(WebhookMessage $message): string
    {
        // webhook is deleted
        if (null === $message->webhook) {
            throw new FireflyException('Part of a deleted webhook.');
        }
        $json      = '';

        try {
            $json = json_encode($message->message, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            app('log')->error('Could not generate hash.');
            app('log')->error(sprintf('JSON value: %s', $json));
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

            throw new FireflyException('Could not generate JSON for SHA3 hash.', 0, $e);
        }

        // signature v1 is generated using the following structure:
        // The signed_payload string is created by concatenating:
        // The timestamp (as a string)
        // The character .
        // The character .
        // The actual JSON payload (i.e., the request body)
        $timestamp = Carbon::now()->getTimestamp();
        $payload   = sprintf('%s.%s', $timestamp, $json);
        $signature = hash_hmac('sha3-256', $payload, (string) $message->webhook->secret, false);

        // signature string:
        // header included in each signed event contains a timestamp and one or more signatures.
        // The timestamp is prefixed by t=, and each signature is prefixed by a scheme.
        // Schemes start with v, followed by an integer. Currently, the only valid live signature scheme is v1.
        return sprintf('t=%s,v%d=%s', $timestamp, $this->getVersion(), $signature);
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
