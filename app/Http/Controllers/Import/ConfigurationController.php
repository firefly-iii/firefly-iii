<?php
/**
 * ConfigurationController.php
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
use FireflyIII\Import\Configuration\ConfiguratorInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\Request;
use Log;

/**
 * Class ConfigurationController
 */
class ConfigurationController extends Controller
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
     * @param ImportJob $job
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     *
     * @throws FireflyException
     */
    public function index(ImportJob $job)
    {
        // create configuration class:
        $configurator = $this->makeConfigurator($job);

        // is the job already configured?
        if ($configurator->isJobConfigured()) {
            $this->repository->updateStatus($job, 'configured');

            return redirect(route('import.status', [$job->key]));
        }

        $this->repository->updateStatus($job, 'configuring');

        $view         = $configurator->getNextView();
        $data         = $configurator->getNextData();
        $subTitle     = trans('firefly.import_config_bread_crumb');
        $subTitleIcon = 'fa-wrench';

        return view($view, compact('data', 'job', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Store the configuration. Returns to "configure" method until job is configured.
     *
     * @param Request   $request
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function post(Request $request, ImportJob $job)
    {
        Log::debug('Now in postConfigure()', ['job' => $job->key]);
        $configurator = $this->makeConfigurator($job);

        // is the job already configured?
        if ($configurator->isJobConfigured()) {
            return redirect(route('import.status', [$job->key]));
        }
        $data = $request->all();
        $configurator->configureJob($data);

        // get possible warning from configurator:
        $warning = $configurator->getWarningMessage();

        if (strlen($warning) > 0) {
            $request->session()->flash('warning', $warning);
        }

        // return to configure
        return redirect(route('import.configure', [$job->key]));
    }

    /**
     * @param ImportJob $job
     *
     * @return ConfiguratorInterface
     *
     * @throws FireflyException
     */
    private function makeConfigurator(ImportJob $job): ConfiguratorInterface
    {
        $type      = $job->file_type;
        $key       = sprintf('import.configuration.%s', $type);
        $className = config($key);
        if (null === $className || !class_exists($className)) {
            throw new FireflyException(sprintf('Cannot find configurator class for job of type "%s".', $type)); // @codeCoverageIgnore
        }
        Log::debug(sprintf('Going to create class "%s"', $className));
        /** @var ConfiguratorInterface $configurator */
        $configurator = app($className);
        $configurator->setJob($job);

        return $configurator;
    }
}
