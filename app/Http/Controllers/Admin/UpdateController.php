<?php
/**
 * UpdateController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
/** @noinspection PhpMethodParametersCountMismatchInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Update\UpdateTrait;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Middleware\IsSandStormUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

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
                app('view')->share('title', (string)trans('firefly.administration'));
                app('view')->share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index']);
        $this->middleware(IsSandStormUser::class)->except(['index']);
    }

    /**
     * Show page with update options.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function index()
    {
        $subTitle        = (string)trans('firefly.update_check_title');
        $subTitleIcon    = 'fa-star';
        $permission      = app('fireflyconfig')->get('permission_update_check', -1);
        $channel         = app('fireflyconfig')->get('update_channel', 'stable');
        $selected        = $permission->data;
        $channelSelected = $channel->data;
        $options         = [
            -1 => (string)trans('firefly.updates_ask_me_later'),
            0  => (string)trans('firefly.updates_do_not_check'),
            1  => (string)trans('firefly.updates_enable_check'),
        ];

        $channelOptions = [
            'stable' => (string)trans('firefly.update_channel_stable'),
            'beta'   => (string)trans('firefly.update_channel_beta'),
            'alpha'  => (string)trans('firefly.update_channel_alpha'),
        ];

        return view('admin.update.index', compact('subTitle', 'subTitleIcon', 'selected', 'options', 'channelSelected', 'channelOptions'));
    }

    /**
     * Post new settings.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function post(Request $request)
    {
        $checkForUpdates = (int)$request->get('check_for_updates');
        $channel         = $request->get('update_channel');
        $channel         = in_array($channel, ['stable', 'beta', 'alpha'], true) ? $channel : 'stable';
        app('fireflyconfig')->set('permission_update_check', $checkForUpdates);
        app('fireflyconfig')->set('last_update_check', time());
        app('fireflyconfig')->set('update_channel', $channel);
        session()->flash('success', (string)trans('firefly.configuration_updated'));

        return redirect(route('admin.update-check'));
    }

    /**
     * Does a manual update check.
     */
    public function updateCheck(): JsonResponse
    {
        $success       = true;
        $latestRelease = '1.0';
        $resultString  = '';
        $versionCheck  = -2;
        try {
            $latestRelease = $this->getLatestRelease();
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            $success = false;
        }

        // if error, tell the user.
        if (false === $success) {
            $resultString = (string)trans('firefly.update_check_error');
            session()->flash('error', $resultString);
        }

        // if not, compare and tell the user.
        if (true === $success) {
            $versionCheck = $this->versionCheck($latestRelease);
            $resultString = $this->parseResult($versionCheck, $latestRelease);
        }

        Log::debug(sprintf('Result string is: "%s"', $resultString));

        if (0 !== $versionCheck && '' !== $resultString) {
            // flash info
            session()->flash('info', $resultString);
        }
        app('fireflyconfig')->set('last_update_check', time());

        return response()->json(['result' => $resultString]);
    }
}
