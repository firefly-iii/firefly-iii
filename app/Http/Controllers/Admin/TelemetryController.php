<?php
declare(strict_types=1);
/**
 * TelemetryController.php
 * Copyright (c) 2020 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Admin;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Jobs\SubmitTelemetryData;
use FireflyIII\Repositories\Telemetry\TelemetryRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

/**
 * Class TelemetryController
 */
class TelemetryController extends Controller
{
    /** @var TelemetryRepositoryInterface */
    private $repository;

    public function __construct()
    {
        if (false === config('firefly.feature_flags.telemetry')) {
            die('Telemetry is disabled.');
        }
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.administration'));
                app('view')->share('mainTitleIcon', 'fa-hand-spock-o');
                $this->repository = app(TelemetryRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function deleteSubmitted()
    {
        $this->repository->deleteSubmitted();

        session()->flash('success', trans('firefly.telemetry_submitted_deleted'));

        return redirect(url()->previous(route('admin.telemetry.index')));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function deleteAll()
    {
        $this->repository->deleteAll();

        session()->flash('success', trans('firefly.telemetry_all_deleted'));

        return redirect(url()->previous(route('admin.telemetry.index')));
    }

    /**
     * Run job to submit telemetry.
     */
    public function submit()
    {
        $job = app(SubmitTelemetryData::class);
        $job->setDate(new Carbon);
        $job->setForce(true);
        $job->handle();
        session()->flash('info', trans('firefly.telemetry_submission_executed'));

        return redirect(url()->previous(route('admin.telemetry.index')));
    }

    /**
     * Index
     */
    public function index()
    {
        app('view')->share('subTitleIcon', 'fa-eye');
        app('view')->share('subTitle', (string) trans('firefly.telemetry_admin_index'));
        $version = config('firefly.version');
        $enabled = config('firefly.send_telemetry', false) && config('firefly.feature_flags.telemetry');

        $count   = $this->repository->count();

        return view('admin.telemetry.index', compact('version', 'enabled', 'count'));
    }

    /**
     * View telemetry. Paginated because you never know how much will be collected.
     *
     * @return Factory|View
     */
    public function view()
    {
        $format  = trans('config.date_time');
        $size    = 100;
        $records = $this->repository->paginated($size);

        return view('admin.telemetry.view', compact('records', 'format'));
    }
}
