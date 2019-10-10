<?php
/**
 * JobConfigurationController.php
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
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class JobConfigurationController
 */
class JobConfigurationController extends Controller
{
    use CreateStuff;
    /** @var ImportJobRepositoryInterface The import job repository */
    public $repository;

    /**
     * JobConfigurationController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-archive');
                app('view')->share('title', (string)trans('firefly.import_index_title'));
                $this->repository = app(ImportJobRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Configure the job. This method is returned to until job is deemed "configured".
     *
     * @param ImportJob $importJob
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     *
     * @throws FireflyException
     *
     */
    public function index(ImportJob $importJob)
    {
        Log::debug('Now in JobConfigurationController::index()');
        $allowed = ['has_prereq', 'need_job_config'];
        if (null !== $importJob && !in_array($importJob->status, $allowed, true)) {
            Log::error(sprintf('Job has state "%s", but we only accept %s', $importJob->status, json_encode($allowed)));
            session()->flash('error', (string)trans('import.bad_job_status', ['status' => e($importJob->status)]));

            return redirect(route('import.index'));
        }
        Log::debug(sprintf('Now in JobConfigurationController::index() with job "%s" and status "%s"', $importJob->key, $importJob->status));

        // if provider has no config, just push it through:
        $importProvider = $importJob->provider;
        if (!(bool)config(sprintf('import.has_job_config.%s', $importProvider))) {
            // @codeCoverageIgnoreStart
            Log::debug('Job needs no config, is ready to run!');
            $this->repository->setStatus($importJob, 'ready_to_run');

            return redirect(route('import.job.status.index', [$importJob->key]));
            // @codeCoverageIgnoreEnd
        }

        $configurator = $this->makeConfigurator($importJob);
        if ($configurator->configurationComplete()) {
            Log::debug('Config is complete, set status to ready_to_run.');
            $this->repository->setStatus($importJob, 'ready_to_run');

            return redirect(route('import.job.status.index', [$importJob->key]));
        }

        $view         = $configurator->getNextView();
        $data         = $configurator->getNextData();
        $subTitle     = (string)trans('import.job_configuration_breadcrumb', ['key' => $importJob->key]);
        $subTitleIcon = 'fa-wrench';

        return view($view, compact('data', 'importJob', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Store the configuration. Returns to "configure" method until job is configured.
     *
     * @param Request   $request
     * @param ImportJob $importJob
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function post(Request $request, ImportJob $importJob)
    {
        // catch impossible status:
        $allowed = ['has_prereq', 'need_job_config'];
        if (null !== $importJob && !in_array($importJob->status, $allowed, true)) {
            session()->flash('error', (string)trans('import.bad_job_status', ['status' => e($importJob->status)]));

            return redirect(route('import.index'));
        }

        Log::debug('Now in postConfigure()', ['job' => $importJob->key]);
        $configurator = $this->makeConfigurator($importJob);

        // is the job already configured?
        if ($configurator->configurationComplete()) {
            $this->repository->setStatus($importJob, 'ready_to_run');

            return redirect(route('import.job.status.index', [$importJob->key]));
        }

        // uploaded files are attached to the job.
        // the configurator can then handle them.
        $result = new MessageBag;

        /** @var UploadedFile $upload */
        foreach ($request->allFiles() as $name => $upload) {
            $result = $this->repository->storeFileUpload($importJob, $name, $upload);
        }
        $data     = $request->all();
        $messages = $configurator->configureJob($data);
        $result->merge($messages);

        if ($messages->count() > 0) {
            $request->session()->flash('warning', $messages->first());
        }

        // return to configure
        return redirect(route('import.job.configuration.index', [$importJob->key]));
    }


}
