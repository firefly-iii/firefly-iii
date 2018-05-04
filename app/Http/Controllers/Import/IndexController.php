<?php
/**
 * IndexController.php
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
use FireflyIII\Import\Prerequisites\PrerequisitesInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use View;


/**
 * Class FileController.
 */
class IndexController extends Controller
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
    }

    /**
     * Creates a new import job for $importProvider.
     *
     * @param string $importProvider
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function create(string $importProvider)
    {
        if (
            !(bool)config('app.debug')
            && !(bool)config(sprintf('import.enabled.%s', $importProvider)) === true
            && !\in_array(config('app.env'), ['demo', 'testing'])
        ) {
            throw new FireflyException(sprintf('Import using provider "%s" is currently not available.', $importProvider)); // @codeCoverageIgnore
        }

        $importJob = $this->repository->create($importProvider);

        // if job provider has no prerequisites:
        if (!(bool)config(sprintf('import.has_prereq.%s', $importProvider))) {
            // @codeCoverageIgnoreStart
            // if job provider also has no configuration:
            if (!(bool)config(sprintf('import.has_config.%s', $importProvider))) {
                $this->repository->updateStatus($importJob, 'ready_to_run');

                return redirect(route('import.job.status.index', [$importJob->key]));
            }

            // update job to say "has_prereq".
            $this->repository->setStatus($importJob, 'has_prereq');

            // redirect to job configuration.
            return redirect(route('import.job.configuration.index', [$importJob->key]));
            // @codeCoverageIgnoreEnd
        }

        // if need to set prerequisites, do that first.
        $class = (string)config(sprintf('import.prerequisites.%s', $importProvider));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('No class to handle configuration for "%s".', $importProvider)); // @codeCoverageIgnore
        }
        /** @var PrerequisitesInterface $providerPre */
        $providerPre = app($class);
        $providerPre->setUser(auth()->user());

        if (!$providerPre->isComplete()) {
            // redirect to global prerequisites
            return redirect(route('import.prerequisites.index', [$importProvider, $importJob->key]));
        }

        // update job to say "has_prereq".
        $this->repository->setStatus($importJob, 'has_prereq');

        // Otherwise just redirect to job configuration.
        return redirect(route('import.job.configuration.index', [$importJob->key]));

    }


    /**
     * General import index.
     *
     * @return View
     */
    public function index()
    {
        // get all import routines:
        /** @var array $config */
        $config    = config('import.enabled');
        $providers = [];
        foreach ($config as $name => $enabled) {
            if ($enabled || (bool)config('app.debug') || \in_array(config('app.env'), ['demo', 'testing'])) {
                $providers[$name] = [];
            }
        }

        // has prereq or config?
        foreach (array_keys($providers) as $name) {
            $providers[$name]['has_prereq'] = (bool)config('import.has_prereq.' . $name);
            $providers[$name]['has_config'] = (bool)config('import.has_config.' . $name);
            $class                          = (string)config('import.prerequisites.' . $name);
            $result                         = false;
            if ($class !== '' && class_exists($class)) {
                /** @var PrerequisitesInterface $object */
                $object = app($class);
                $object->setUser(auth()->user());
                $result = $object->isComplete();
            }
            $providers[$name]['prereq_complete'] = $result;
        }

        $subTitle     = trans('import.index_breadcrumb');
        $subTitleIcon = 'fa-home';

        return view('import.index', compact('subTitle', 'subTitleIcon', 'providers'));
    }
}
