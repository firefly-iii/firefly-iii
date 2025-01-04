<?php

/*
 * NotificationController.php
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

namespace FireflyIII\Http\Controllers\Admin;

use FireflyIII\Events\Test\OwnerTestNotificationChannel;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\NotificationRequest;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        Log::channel('audit')->info('User visits notifications index.');
        $title                          = (string) trans('firefly.administration');
        $mainTitleIcon                  = 'fa-hand-spock-o';
        $subTitle                       = (string) trans('firefly.title_owner_notifications');
        $subTitleIcon                   = 'envelope-o';

        // notification settings:
        $slackUrl                       = app('fireflyconfig')->getEncrypted('slack_webhook_url', '')->data;
        $pushoverAppToken               = app('fireflyconfig')->getEncrypted('pushover_app_token', '')->data;
        $pushoverUserToken              = app('fireflyconfig')->getEncrypted('pushover_user_token', '')->data;
        $ntfyServer                     = app('fireflyconfig')->getEncrypted('ntfy_server', 'https://ntfy.sh')->data;
        $ntfyTopic                      = app('fireflyconfig')->getEncrypted('ntfy_topic', '')->data;
        $ntfyAuth                       = app('fireflyconfig')->get('ntfy_auth', false)->data;
        $ntfyUser                       = app('fireflyconfig')->getEncrypted('ntfy_user', '')->data;
        $ntfyPass                       = app('fireflyconfig')->getEncrypted('ntfy_pass', '')->data;
        $channels                       = config('notifications.channels');
        $forcedAvailability             = [];

        // admin notification settings:
        $notifications                  = [];
        foreach (config('notifications.notifications.owner') as $key => $info) {
            if (true === $info['enabled']) {
                $notifications[$key] = app('fireflyconfig')->get(sprintf('notification_%s', $key), true)->data;
            }
        }

        // loop all channels to see if they are available.
        foreach ($channels as $channel => $info) {
            $forcedAvailability[$channel] = true;
        }
        $forcedAvailability['ntfy']     = '' !== $ntfyTopic;
        $forcedAvailability['pushover'] = '' !== $pushoverAppToken && '' !== $pushoverUserToken;

        return view(
            'admin.notifications.index',
            compact(
                'title',
                'subTitle',
                'forcedAvailability',
                'mainTitleIcon',
                'subTitleIcon',
                'channels',
                'slackUrl',
                'notifications',
                'pushoverAppToken',
                'pushoverUserToken',
                'ntfyServer',
                'ntfyTopic',
                'ntfyAuth',
                'ntfyUser',
                'ntfyPass'
            )
        );
    }

    public function postIndex(NotificationRequest $request): RedirectResponse
    {
        $all       = $request->getAll();

        foreach (config('notifications.notifications.owner') as $key => $info) {
            if (array_key_exists($key, $all)) {
                app('fireflyconfig')->set(sprintf('notification_%s', $key), $all[$key]);
            }
        }
        $variables = ['slack_webhook_url', 'pushover_app_token', 'pushover_user_token', 'ntfy_server', 'ntfy_topic', 'ntfy_user', 'ntfy_pass'];
        foreach ($variables as $variable) {
            if ('' === $all[$variable]) {
                app('fireflyconfig')->delete($variable);
            }
            if ('' !== $all[$variable]) {
                app('fireflyconfig')->setEncrypted($variable, $all[$variable]);
            }
        }
        app('fireflyconfig')->set('ntfy_auth', $all['ntfy_auth'] ?? false);


        session()->flash('success', (string) trans('firefly.notification_settings_saved'));

        return redirect(route('admin.notification.index'));
    }

    public function testNotification(Request $request): RedirectResponse
    {

        $all     = $request->all();
        $channel = $all['test_submit'] ?? '';

        switch ($channel) {
            default:
                session()->flash('error', (string) trans('firefly.notification_test_failed', ['channel' => $channel]));

                break;

            case 'email':
            case 'slack':
            case 'pushover':
            case 'ntfy':
                $owner = new OwnerNotifiable();
                app('log')->debug(sprintf('Now in testNotification("%s") controller.', $channel));
                event(new OwnerTestNotificationChannel($channel, $owner));
                session()->flash('success', (string) trans('firefly.notification_test_executed', ['channel' => $channel]));
        }

        return redirect(route('admin.notification.index'));
    }
}
