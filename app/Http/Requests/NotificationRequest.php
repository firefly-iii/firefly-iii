<?php
/*
 * NotificationRequest.php
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

use FireflyIII\Rules\Admin\IsValidDiscordUrl;
use FireflyIII\Rules\Admin\IsValidSlackUrl;
use FireflyIII\Support\Request\ChecksLogin;
use FireflyIII\Support\Request\ConvertsDataTypes;
use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    use ChecksLogin;
    use ConvertsDataTypes;

    public function getAll(): array
    {
        $return = [];
        foreach (config('notifications.notifications.owner') as $key => $info) {
            $value = false;
            if ($this->has(sprintf('notification_%s', $key))) {
                $value = true;
            }
            $return[$key] = $value;
        }
        $return['slack_url']   = $this->convertString('slack_url');
        return $return;
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        $rules = [
            'slack_url'   => ['nullable', 'url', 'min:1', new IsValidSlackUrl()],
        ];
        foreach (config('notifications.notifications.owner') as $key => $info) {
            $rules[sprintf('notification_%s', $key)] = 'in:0,1';
        }
        return $rules;
    }

}
