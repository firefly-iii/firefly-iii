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

use FireflyIII\Events\Test\TestNotificationChannel;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\NotificationRequest;
use FireflyIII\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index()
    {
        Log::channel('audit')->info('User visits notifications index.');
        $title         = (string) trans('firefly.administration');
        $mainTitleIcon = 'fa-hand-spock-o';
        $subTitle      = (string) trans('firefly.title_owner_notifications');
        $subTitleIcon  = 'envelope-o';
        $slackUrl      = app('fireflyconfig')->get('slack_webhook_url', '')->data;
        $discordUrl    = app('fireflyconfig')->get('discord_webhook_url', '')->data;
        $channels      = config('notifications.channels');


        // admin notification settings:
        $notifications = [];
        foreach (config('notifications.notifications.owner') as $key => $info) {
            if ($info['enabled']) {
                $notifications[$key] = app('fireflyconfig')->get(sprintf('notification_%s', $key), true)->data;
            }
        }


        return view('admin.notifications.index', compact('title', 'subTitle', 'mainTitleIcon', 'subTitleIcon', 'channels', 'slackUrl', 'discordUrl', 'notifications'));
    }

    public function postIndex(NotificationRequest $request): RedirectResponse
    {
        $all = $request->getAll();

        foreach (config('notifications.notifications.owner') as $key => $info) {
            if (array_key_exists($key, $all)) {
                app('fireflyconfig')->set(sprintf('notification_%s', $key), $all[$key]);
            }
        }
        if ('' === $all['slack_url']) {
            app('fireflyconfig')->delete('slack_webhook_url');
        }
        if ('' === $all['discord_url']) {
            app('fireflyconfig')->delete('discord_webhook_url');
        }
        if ('' !== $all['slack_url']) {
            app('fireflyconfig')->set('slack_webhook_url', $all['slack_url']);
        }
        if ('' !== $all['discord_url']) {
            app('fireflyconfig')->set('discord_webhook_url', $all['discord_url']);
        }

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
            case 'discord':
            case 'slack':
                /** @var User $user */
                $user = auth()->user();
                app('log')->debug(sprintf('Now in testNotification("%s") controller.', $channel));
                event(new TestNotificationChannel($channel, $user));
                session()->flash('success', (string) trans('firefly.notification_test_executed', ['channel' => $channel]));
        }

        return redirect(route('admin.notification.index'));
    }
}
