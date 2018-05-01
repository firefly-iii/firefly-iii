<?php
/**
 * JobConfigurationController.php
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

namespace FireflyIII\Http\Controllers\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Import\JobConfiguration\JobConfiguratorInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\Request;
use Log;

/**
 * Class JobConfigurationController
 */
class JobConfigurationController extends Controller
{
    /** @var ImportJobRepositoryInterface */
    public $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-archive');
                app('view')->share('title', trans('firefly.import_index_title'));
                $this->repository = app(ImportJobRepositoryInterface::class);

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class);
    }

    /**
     * Configure the job. This method is returned to until job is deemed "configured".
     *
     * @param ImportJob $importJob
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     *
     * @throws FireflyException
     */
    public function index(ImportJob $importJob)
    {
        // catch impossible status:
        $allowed = ['has_prereq', 'need_job_config', 'has_config'];
        if (null !== $importJob && !in_array($importJob->status, $allowed)) {
            Log::error('Job is not new but wants to do prerequisites');
            session()->flash('error', trans('import.bad_job_status'));
            return redirect(route('import.index'));
        }

        Log::debug(sprintf('Now in JobConfigurationController::index() with job "%s" and status "%s"', $importJob->key, $importJob->status));

        // if provider has no config, just push it through:
        $importProvider = $importJob->provider;
        if (!(bool)config(sprintf('import.has_config.%s', $importProvider))) {
            Log::debug('Job needs no config, is ready to run!');
            $this->repository->updateStatus($importJob ,'ready_to_run');

            return redirect(route('import.job.status.index', [$importProvider->key]));
        }

        // create configuration class:
        $configurator = $this->makeConfigurator($importJob);

        // is the job already configured?
        if ($configurator->configurationComplete()) {
            Log::debug('Config is complete, set status to ready_to_run.');
            $this->repository->updateStatus($importJob, 'ready_to_run');

            return redirect(route('import.job.status.index', [$importJob->key]));
        }

        $view         = $configurator->getNextView();
        $data         = $configurator->getNextData();
        $subTitle     = trans('firefly.import_config_bread_crumb');
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
        $allowed = ['has_prereq', 'need_job_config', 'has_config'];
        if (null !== $importJob && !in_array($importJob->status, $allowed)) {
            Log::error('Job is not new but wants to do prerequisites');
            session()->flash('error', trans('import.bad_job_status'));
            return redirect(route('import.index'));
        }

        Log::debug('Now in postConfigure()', ['job' => $importJob->key]);
        $configurator = $this->makeConfigurator($importJob);

        // is the job already configured?
        if ($configurator->configurationComplete()) {
            $this->repository->updateStatus($importJob, 'ready_to_run');

            return redirect(route('import.job.status.index', [$importJob->key]));
        }

        $data     = $request->all();
        $messages = $configurator->configureJob($data);

        if ($messages->count() > 0) {
            $request->session()->flash('warning', $messages->first());
        }

        // return to configure
        return redirect(route('import.job.configuration.index', [$importJob->key]));
    }

    /**
     * @param ImportJob $importJob
     *
     * @return JobConfiguratorInterface
     *
     * @throws FireflyException
     */
    private function makeConfigurator(ImportJob $importJob): JobConfiguratorInterface
    {
        $key       = sprintf('import.configuration.%s', $importJob->provider);
        $className = (string)config($key);
        if (null === $className || !class_exists($className)) {
            throw new FireflyException(sprintf('Cannot find configurator class for job with provider "%s".', $importJob->provider)); // @codeCoverageIgnore
        }
        Log::debug(sprintf('Going to create class "%s"', $className));
        /** @var JobConfiguratorInterface $configurator */
        $configurator = app($className);
        $configurator->setJob($importJob);

        return $configurator;
    }
}
