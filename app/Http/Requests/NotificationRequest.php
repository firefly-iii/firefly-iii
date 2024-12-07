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
        $return['discord_url'] = $this->convertString('discordUrl');
        $return['slack_url']   = $this->convertString('slackUrl');
        return $return;
//            if (UrlValidator::isValidWebhookURL($url)) {
//                app('fireflyconfig')->set('slack_webhook_url', $url);
//            }
//        }
//
//
//        var_dump($this->all());
//        exit;
//        return [];
    }

    /**
     * Rules for this request.
     */
    public function rules(): array
    {
        // fixed
        return [
            //'password' => 'required',
        ];
    }

}
