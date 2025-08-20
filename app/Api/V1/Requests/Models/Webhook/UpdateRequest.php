<?php

/*
 * UpdateRequest.php
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

namespace FireflyIII\Api\V1\Requests\Models\Webhook;

use FireflyIII\Enums\WebhookResponse;
use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Webhook;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getData(): array
    {
        $fields           = [
            'title'    => ['title', 'convertString'],
            'active'   => ['active', 'boolean'],
            'url'      => ['url', 'convertString'],
        ];

        $triggers   = $this->get('triggers', []);
        $responses  = $this->get('responses', []);
        $deliveries = $this->get('deliveries', []);

        if (0 === count($triggers) || 0 === count($responses) || 0 === count($deliveries)) {
            throw new FireflyException('Unexpectedly got no responses, triggers or deliveries.');
        }

        $return               = $this->getAllData($fields);
        $return['triggers']   = $triggers;
        $return['responses']  = $responses;
        $return['deliveries'] = $deliveries;

        return $return;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        $triggers       = implode(',', array_values(Webhook::getTriggers()));
        $responses      = implode(',', array_values(Webhook::getResponses()));
        $deliveries     = implode(',', array_values(Webhook::getDeliveries()));
        $validProtocols = config('firefly.valid_url_protocols');

        /** @var Webhook $webhook */
        $webhook        = $this->route()->parameter('webhook');

        return [
            'title'    => sprintf('min:1|max:255|uniqueObjectForUser:webhooks,title,%d', $webhook->id),
            'active'   => [new IsBoolean()],

            'trigger'      => 'prohibited',
            'triggers'     => 'required|array|min:1|max:10',
            'triggers.*'   => sprintf('required|in:%s', $triggers),
            'response'     => 'prohibited',
            'responses'    => 'required|array|min:1|max:1',
            'responses.*'  => sprintf('required|in:%s', $responses),
            'delivery'     => 'prohibited',
            'deliveries'   => 'required|array|min:1|max:1',
            'deliveries.*' => sprintf('required|in:%s', $deliveries),

            'url'      => [sprintf('url:%s', $validProtocols), sprintf('uniqueExistingWebhook:%d', $webhook->id)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator): void {
                Log::debug('Validating webhook');

                if ($validator->failed()) {
                    return;
                }
                $data      = $validator->getData();
                $triggers  = $data['triggers'] ?? [];
                $responses = $data['responses'] ?? [];

                if (0 === count($triggers) || 0 === count($responses)) {
                    Log::debug('No trigger or response, return.');

                    return;
                }
                $validTriggers  = array_values(Webhook::getTriggers());
                $validResponses = array_values(Webhook::getResponses());
                foreach ($triggers as $trigger) {
                    if (!in_array($trigger, $validTriggers, true)) {
                        return;
                    }
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
                        $validator->errors()->add(sprintf('triggers.%d', $i), trans('validation.unknown_webhook_trigger', ['trigger' => $trigger,]));
                        continue;
                    }
                    foreach ($responses as $ii => $response) {
                        if (in_array($response, $forbidden, true)) {
                            Log::debug(sprintf('Trigger %s and response %s are forbidden.', $trigger, $response));
                            $validator->errors()->add(sprintf('responses.%d', $ii), trans('validation.bad_webhook_combination', ['trigger' => $trigger, 'response' => $response,]));
                            return;
                        }
                    }
                }

            }
        );
    }
}
