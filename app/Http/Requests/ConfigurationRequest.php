<?php

/**
 * ConfigurationRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Support\Request\ChecksLogin;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class ConfigurationRequest.
 */
class ConfigurationRequest extends FormRequest
{
    use ChecksLogin;

    /**
     * Returns the data required by the controller.
     */
    public function getConfigurationData(): array
    {
        return [
            'single_user_mode'      => $this->boolean('single_user_mode'),

            'enable_exchange_rates' => $this->boolean('enable_exchange_rates'),
            'use_running_balance'   => $this->boolean('use_running_balance'),

            'enable_external_map'   => $this->boolean('enable_external_map'),
            'enable_external_rates' => $this->boolean('enable_external_rates'),
            'allow_webhooks'        => $this->boolean('allow_webhooks'),

            'valid_url_protocols'   => $this->string('valid_url_protocols'),
            'is_demo_site'          => $this->boolean('is_demo_site'),
        ];
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        // fixed
        return [
            'single_user_mode'      => 'min:0|max:1|numeric',

            'enable_exchange_rates' => 'min:0|max:1|numeric',
            'use_running_balance'   => 'min:0|max:1|numeric',

            'enable_external_map'   => 'min:0|max:1|numeric',
            'enable_external_rates' => 'min:0|max:1|numeric',
            'allow_webhooks'        => 'min:0|max:1|numeric',

            'valid_url_protocols'   => 'min:0|max:255',
            'is_demo_site'          => 'min:0|max:1|numeric',


        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($validator->fails()) {
            Log::channel('audit')->error(sprintf('Validation errors in %s', self::class), $validator->errors()->toArray());
        }
    }
}
