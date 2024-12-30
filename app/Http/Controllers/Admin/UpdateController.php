<?php

/**
 * UpdateController.php
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

use FireflyIII\Helpers\Update\UpdateTrait;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class HomeController.
 */
class UpdateController extends Controller
{
    use UpdateTrait;

    /**
     * ConfigurationController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            static function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.administration'));
                app('view')->share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index']);
    }

    /**
     * Show page with update options.
     *
     * @return Factory|View
     */
    public function index()
    {
        $subTitle        = (string) trans('firefly.update_check_title');
        $subTitleIcon    = 'fa-star';
        $permission      = app('fireflyconfig')->get('permission_update_check', -1);
        $channel         = app('fireflyconfig')->get('update_channel', 'stable');
        $selected        = $permission->data;
        $channelSelected = $channel->data;
        $options         = [
            -1 => (string) trans('firefly.updates_ask_me_later'),
            0  => (string) trans('firefly.updates_do_not_check'),
            1  => (string) trans('firefly.updates_enable_check'),
        ];

        $channelOptions  = [
            'stable' => (string) trans('firefly.update_channel_stable'),
            'beta'   => (string) trans('firefly.update_channel_beta'),
            'alpha'  => (string) trans('firefly.update_channel_alpha'),
        ];

        return view('admin.update.index', compact('subTitle', 'subTitleIcon', 'selected', 'options', 'channelSelected', 'channelOptions'));
    }

    /**
     * Post new settings.
     *
     * @return Redirector|RedirectResponse
     */
    public function post(Request $request)
    {
        $checkForUpdates = (int) $request->get('check_for_updates');
        $channel         = $request->get('update_channel');
        $channel         = in_array($channel, ['stable', 'beta', 'alpha'], true) ? $channel : 'stable';

        app('fireflyconfig')->set('permission_update_check', $checkForUpdates);
        app('fireflyconfig')->set('last_update_check', time());
        app('fireflyconfig')->set('update_channel', $channel);
        session()->flash('success', (string) trans('firefly.configuration_updated'));

        return redirect(route('admin.update-check'));
    }

    /**
     * Does a manual update check.
     */
    public function updateCheck(): RedirectResponse
    {
        $release = $this->getLatestRelease();

        session()->flash($release['level'], $release['message']);

        return redirect(route('admin.update-check'));
    }
}
