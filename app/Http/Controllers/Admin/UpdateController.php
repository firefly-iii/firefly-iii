<?php
/**
 * UpdateController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Admin;

use FireflyConfig;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Middleware\IsSandStormUser;
use FireflyIII\Services\Github\Object\Release;
use FireflyIII\Services\Github\Request\UpdateRequest;
use Illuminate\Http\Request;
use Log;
use Response;
use Session;

/**
 * Class HomeController.
 */
class UpdateController extends Controller
{


    /**
     * ConfigurationController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', strval(trans('firefly.administration')));
                app('view')->share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index']);
        $this->middleware(IsSandStormUser::class)->except(['index']);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public function index()
    {
        $subTitle     = trans('firefly.update_check_title');
        $subTitleIcon = 'fa-star';
        $permission   = app('fireflyconfig')->get('permission_update_check', -1);
        $selected     = $permission->data;
        $options      = [
            '-1' => trans('firefly.updates_ask_me_later'),
            '0'  => trans('firefly.updates_do_not_check'),
            '1'  => trans('firefly.updates_enable_check'),
        ];

        return view('admin.update.index', compact('subTitle', 'subTitleIcon', 'selected', 'options'));
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function post(Request $request)
    {
        $checkForUpdates = intval($request->get('check_for_updates'));
        FireflyConfig::set('permission_update_check', $checkForUpdates);
        FireflyConfig::set('last_update_check', time());
        Session::flash('success', strval(trans('firefly.configuration_updated')));

        return redirect(route('admin.update-check'));
    }

    /**
     * Does a manual update check.
     */
    public function updateCheck()
    {
        $current = config('firefly.version');
        /** @var UpdateRequest $request */
        $request = app(UpdateRequest::class);
        $check   = -2;
        $first   = new Release(['id' => '0', 'title' => '0', 'updated' => '2017-01-01', 'content' => '']);
        $string  = '';
        try {
            $request->call();
            $releases = $request->getReleases();
            // first entry should be the latest entry:
            /** @var Release $first */
            $first = reset($releases);
            $check = version_compare($current, $first->getTitle());
            FireflyConfig::set('last_update_check', time());
        } catch (FireflyException $e) {
            Log::error(sprintf('Could not check for updates: %s', $e->getMessage()));
        }
        if ($check === -2) {
            $string = strval(trans('firefly.update_check_error'));
        }

        if ($check === -1) {
            // there is a new FF version!
            $string = strval(
                trans(
                    'firefly.update_new_version_alert',
                    ['your_version' => $current, 'new_version' => $first->getTitle(), 'date' => $first->getUpdated()->formatLocalized($this->monthAndDayFormat)]
                )
            );
        }
        if ($check === 0) {
            // you are running the current version!
            $string = strval(trans('firefly.update_current_version_alert', ['version' => $current]));
        }
        if ($check === 1) {
            // you are running a newer version!
            $string = strval(trans('firefly.update_newer_version_alert', ['your_version' => $current, 'new_version' => $first->getTitle()]));
        }

        return Response::json(['result' => $string]);
    }
}
