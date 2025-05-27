<?php

/**
 * PreferenceTransformer.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Transformers;

use FireflyIII\Models\WebhookMessage;
use JsonException;

use function Safe\json_encode;

/**
 * Class WebhookMessageTransformer
 */
class WebhookMessageTransformer extends AbstractTransformer
{
    /**
     * Transform the preference
     */
    public function transform(WebhookMessage $message): array
    {
        $json = '{}';

        try {
            $json = json_encode($message->message, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            app('log')->error(sprintf('Could not encode webhook message #%d: %s', $message->id, $e->getMessage()));
        }

        return [
            'id'         => (string) $message->id,
            'created_at' => $message->created_at->toAtomString(),
            'updated_at' => $message->updated_at->toAtomString(),
            'sent'       => $message->sent,
            'errored'    => $message->errored,
            'webhook_id' => (string) $message->webhook_id,
            'uuid'       => $message->uuid,
            'message'    => $json,
        ];
    }
}
