<?php

/*
 * WebhookTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Enums\WebhookDelivery;
use FireflyIII\Enums\WebhookResponse;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Models\Webhook;

/**
 * Class WebhookTransformer
 */
class WebhookTransformer extends AbstractTransformer
{
    /**
     * WebhookTransformer constructor.
     */
    public function __construct() {}

    /**
     * Transform webhook.
     */
    public function transform(Webhook $webhook): array
    {
        return [
            'id'         => $webhook->id,
            'created_at' => $webhook->created_at->toAtomString(),
            'updated_at' => $webhook->updated_at->toAtomString(),
            'active'     => $webhook->active,
            'title'      => $webhook->title,
            'secret'     => $webhook->secret,
            'trigger'    => $this->getEnum('trigger', $webhook->trigger),
            'response'   => $this->getEnum('response', $webhook->response),
            'delivery'   => $this->getEnum('delivery', $webhook->delivery),
            'url'        => $webhook->url,
            'links'      => [
                [
                    'rel' => 'self',
                    'uri' => sprintf('/webhooks/%d', $webhook->id),
                ],
            ],
        ];
    }

    private function getEnum(string $type, int $value): string
    {
        if ('trigger' === $type) {
            return WebhookTrigger::from($value)->name;
        }
        if ('response' === $type) {
            return WebhookResponse::from($value)->name;
        }

        return WebhookDelivery::from($value)->name;
    }
}
