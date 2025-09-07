<?php


/*
 * ValidatesWebhooks.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Support\Request;

use Illuminate\Validation\Validator;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Models\Webhook;
use Illuminate\Support\Facades\Log;

trait ValidatesWebhooks
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                Log::debug('Validating webhook');
                if (count($validator->failed()) > 0) {
                    return;
                }
                $data           = $validator->getData();
                $triggers       = $data['triggers'] ?? [];
                $responses      = $data['responses'] ?? [];

                if (0 === count($triggers) || 0 === count($responses)) {
                    Log::debug('No trigger or response, return.');

                    return;
                }
                $validTriggers  = array_values(Webhook::getTriggers());
                $validResponses = array_values(Webhook::getResponses());
                $containsAny    = false;
                $count          = 0;
                foreach ($triggers as $trigger) {
                    if (!in_array($trigger, $validTriggers, true)) {
                        return;
                    }
                    ++$count;
                    if ($trigger === WebhookTrigger::ANY->name) {
                        $containsAny = true;
                    }
                }
                if ($containsAny && $count > 1) {
                    $validator->errors()->add('triggers.0', trans('validation.only_any_trigger'));

                    return;
                }
                foreach ($responses as $response) {
                    if (!in_array($response, $validResponses, true)) {
                        return;
                    }
                }
                // some combinations are illegal.
                foreach ($triggers as $i => $trigger) {
                    $forbidden = config(sprintf('webhooks.forbidden_responses.%s', $trigger));
                    if (null === $forbidden) {
                        $validator->errors()->add(sprintf('triggers.%d', $i), trans('validation.unknown_webhook_trigger', ['trigger' => $trigger]));

                        continue;
                    }
                    foreach ($responses as $ii => $response) {
                        if (in_array($response, $forbidden, true)) {
                            Log::debug(sprintf('Trigger %s and response %s are forbidden.', $trigger, $response));
                            $validator->errors()->add(sprintf('responses.%d', $ii), trans('validation.bad_webhook_combination', ['trigger' => $trigger, 'response' => $response]));

                            return;
                        }
                    }
                }
            }
        );
    }
}
