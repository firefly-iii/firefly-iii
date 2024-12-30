<?php

/*
 * PreferencesRequest.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Rules\Admin\IsValidSlackOrDiscordUrl;
use FireflyIII\Support\Request\ChecksLogin;
use Illuminate\Foundation\Http\FormRequest;

class PreferencesRequest extends FormRequest
{
    use ChecksLogin;

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        $rules = [
            'slack_webhook_url' => ['nullable', 'url', 'min:1', new IsValidSlackOrDiscordUrl()],
            'ntfy_server'       => ['nullable', 'url', 'min:1'],
            'ntfy_user'         => ['required_with:ntfy_pass,ntfy_auth', 'nullable', 'string', 'min:1'],
            'ntfy_pass'         => ['required_with:ntfy_user,ntfy_auth', 'nullable', 'string', 'min:1'],
        ];
        foreach (config('notifications.notifications.user') as $key => $info) {
            $rules[sprintf('notification_%s', $key)] = 'in:0,1';
        }

        return $rules;
    }
}
