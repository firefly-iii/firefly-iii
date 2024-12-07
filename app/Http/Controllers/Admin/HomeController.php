<?php

/**
 * HomeController.php
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

namespace FireflyIII\Http\Controllers\Admin;

use FireflyIII\Events\AdminRequestedTestMessage;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Support\Notifications\UrlValidator;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class HomeController.
 */
class HomeController extends Controller
{
    /**
     * ConfigurationController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(IsDemoUser::class)->except(['index']);
    }

    /**
     * Index of the admin.
     *
     * @return Factory|View
     */
    public function index()
    {
        Log::channel('audit')->info('User visits admin index.');
        $title         = (string)trans('firefly.administration');
        $mainTitleIcon = 'fa-hand-spock-o';
        $email         = auth()->user()->email;
        $pref          = app('preferences')->get('remote_guard_alt_email');
        if (null !== $pref && is_string($pref->data)) {
            $email = $pref->data;
        }

        // admin notification settings:
        $notifications = [];
        foreach (config('notifications.notifications.owner') as $key => $info) {
            if($info['enabled']) {
                $notifications[$key] = app('fireflyconfig')->get(sprintf('notification_%s', $key), true)->data;
            }
        }
        //

        return view('admin.index', compact('title', 'mainTitleIcon', 'email', 'notifications'));
    }

    public function notifications(Request $request): RedirectResponse
    {
        foreach (config('notifications.notifications.owner') as $key => $info) {
            $value = false;
            if ($request->has(sprintf('notification_%s', $key))) {
                $value = true;
            }
            app('fireflyconfig')->set(sprintf('notification_%s', $key), $value);
        }
        $url = (string)$request->get('slackUrl');
        if ('' === $url) {
            app('fireflyconfig')->delete('slack_webhook_url');
        }
        if (UrlValidator::isValidWebhookURL($url)) {
            app('fireflyconfig')->set('slack_webhook_url', $url);
        }

        session()->flash('success', (string)trans('firefly.notification_settings_saved'));

        return redirect(route('admin.index'));
    }

    /**
     * Send a test message to the admin.
     *
     * @return Redirector|RedirectResponse
     */
    public function testMessage()
    {
        Log::channel('audit')->info('User sends test message.');

        /** @var User $user */
        $user = auth()->user();
        app('log')->debug('Now in testMessage() controller.');
        event(new AdminRequestedTestMessage($user));
        session()->flash('info', (string)trans('firefly.send_test_triggered'));

        return redirect(route('admin.index'));
    }
}
