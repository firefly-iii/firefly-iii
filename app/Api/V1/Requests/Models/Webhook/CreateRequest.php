<?php

/*
 * CreateRequest.php
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

use FireflyIII\Models\Webhook;
use FireflyIII\Rules\IsBoolean;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateRequest
 */
class CreateRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getData(): array
    {
        $triggers           = Webhook::getTriggersForValidation();
        $responses          = Webhook::getResponsesForValidation();
        $deliveries         = Webhook::getDeliveriesForValidation();

        $fields             = [
            'title'    => ['title', 'convertString'],
            'active'   => ['active', 'boolean'],
            'trigger'  => ['trigger', 'convertString'],
            'response' => ['response', 'convertString'],
            'delivery' => ['delivery', 'convertString'],
            'url'      => ['url', 'convertString'],
        ];

        // this is the way.
        $return             = $this->getAllData($fields);
        $return['trigger']  = $triggers[$return['trigger']] ?? (int) $return['trigger'];
        $return['response'] = $responses[$return['response']] ?? (int) $return['response'];
        $return['delivery'] = $deliveries[$return['delivery']] ?? (int) $return['delivery'];

        return $return;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        $triggers       = implode(',', array_keys(Webhook::getTriggersForValidation()));
        $responses      = implode(',', array_keys(Webhook::getResponsesForValidation()));
        $deliveries     = implode(',', array_keys(Webhook::getDeliveriesForValidation()));
        $validProtocols = config('firefly.valid_url_protocols');

        return [
            'title'    => 'required|min:1|max:255|uniqueObjectForUser:webhooks,title',
            'active'   => [new IsBoolean()],
            'trigger'  => sprintf('required|in:%s', $triggers),
            'response' => sprintf('required|in:%s', $responses),
            'delivery' => sprintf('required|in:%s', $deliveries),
            'url'      => ['required', sprintf('url:%s', $validProtocols), 'uniqueWebhook'],
        ];
    }
}
