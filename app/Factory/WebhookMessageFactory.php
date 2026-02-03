<?php
/*
 * WebhookMessageFactory.php
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

namespace FireflyIII\Factory;

use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookMessage;
use Illuminate\Support\Facades\Log;

class WebhookMessageFactory
{
    public function create(Webhook $webhook, array $data): WebhookMessage {
        $webhookMessage          = new WebhookMessage();
        $webhookMessage->webhook()->associate($webhook);
        $webhookMessage->sent    = false;
        $webhookMessage->errored = false;
        $webhookMessage->uuid    = $data['uuid'];
        $webhookMessage->message = $data;
        $webhookMessage->save();
        Log::debug(sprintf('Stored new webhook message #%d', $webhookMessage->id));
        return $webhookMessage;
    }

}
