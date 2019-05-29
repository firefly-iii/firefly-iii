<?php

/**
 * ConfigurationRequest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Rules\IsBoolean;

/**
 * Class ConfigurationRequest
 * @codeCoverageIgnore
 */
class ConfigurationRequest extends Request
{

    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        $name = $this->route()->parameter('configName');
        switch ($name) {
            case 'is_demo_site':
            case 'single_user_mode':
                return ['value' => $this->boolean('value')];
            case 'permission_update_check':
                return ['value' => $this->integer('value')];
        }

        return ['value' => $this->string('value')]; // @codeCoverageIgnore
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $name = $this->route()->parameter('configName');
        switch ($name) {
            case 'is_demo_site':
            case 'single_user_mode':
                return ['value' => ['required', new IsBoolean]];
            case 'permission_update_check':
                return ['value' => 'required|numeric|between:-1,1'];
        }

        return ['value' => 'required']; // @codeCoverageIgnore
    }
}
