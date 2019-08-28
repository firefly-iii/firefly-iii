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

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Import\Prerequisites\PrerequisitesInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Binder\ImportProvider;
use Illuminate\Http\Response as LaravelResponse;
use Log;

/**
 *
 * Class IndexController
 */
class IndexController extends Controller
{
    /** @var array All available providers */
    public $providers;
    /** @var ImportJobRepositoryInterface The import job repository */
    public $repository;
    /** @var UserRepositoryInterface The user repository */
    public $userRepository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-archive');
                app('view')->share('title', (string)trans('firefly.import_index_title'));
                $this->repository     = app(ImportJobRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->providers      = ImportProvider::getProviders();

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
     */
    public function create(string $importProvider)
    {
        $hasPreReq      = (bool)config(sprintf('import.has_prereq.%s', $importProvider));
        $hasConfig      = (bool)config(sprintf('import.has_job_config.%s', $importProvider));
        $allowedForDemo = (bool)config(sprintf('import.allowed_for_demo.%s', $importProvider));
        $isDemoUser     = $this->userRepository->hasRole(auth()->user(), 'demo');

        Log::debug(sprintf('Will create job for provider "%s"', $importProvider));
        Log::debug(sprintf('Is demo user? %s', var_export($isDemoUser, true)));
        Log::debug(sprintf('Is allowed for user? %s', var_export($allowedForDemo, true)));
        Log::debug(sprintf('Has prerequisites? %s', var_export($hasPreReq, true)));
        Log::debug(sprintf('Has config? %s', var_export($hasConfig, true)));

        // @codeCoverageIgnoreStart
        if ($isDemoUser && !$allowedForDemo) {
            Log::debug('User is demo and this provider doesnt work for demo users.');

            return redirect(route('import.index'));
        }
        // @codeCoverageIgnoreEnd

        $importJob = $this->repository->create($importProvider);

        Log::debug(sprintf('Created job #%d for provider %s', $importJob->id, $importProvider));

        // no prerequisites but job has config:
        if (false === $hasPreReq && false !== $hasConfig) {
            Log::debug('Provider has no prerequisites. Continue.');
            $this->repository->setStatus($importJob, 'has_prereq');
            Log::debug('Redirect to configuration.');

            return redirect(route('import.job.configuration.index', [$importJob->key]));
        }

        // job has prerequisites:
        Log::debug('Job provider has prerequisites.');
        /** @var PrerequisitesInterface $providerPre */
        $providerPre = app((string)config(sprintf('import.prerequisites.%s', $importProvider)));
        $providerPre->setUser($importJob->user);

        // and are not filled in:
        if (!$providerPre->isComplete()) {
            Log::debug('Job provider prerequisites are not yet filled in. Redirect to prerequisites-page.');

            // redirect to global prerequisites
            return redirect(route('import.prerequisites.index', [$importProvider, $importJob->key]));
        }
        Log::debug('Prerequisites are complete.');

        // but are filled in:
        $this->repository->setStatus($importJob, 'has_prereq');

        // and has no config:
        if (false === $hasConfig) {
            // @codeCoverageIgnoreStart
            Log::debug('Provider has no configuration. Job is ready to start.');
            $this->repository->setStatus($importJob, 'ready_to_run');
            Log::debug('Redirect to status-page.');

            return redirect(route('import.job.status.index', [$importJob->key]));
            // @codeCoverageIgnoreEnd
        }

        // but also needs config:
        Log::debug('Job has configuration. Redirect to job-config.');

        // Otherwise just redirect to job configuration.
        return redirect(route('import.job.configuration.index', [$importJob->key]));

    }

    /**
     * Generate a JSON file of the job's configuration and send it to the user.
     *
     * @param ImportJob $job
     *
     * @return LaravelResponse
     */
    public function download(ImportJob $job): LaravelResponse
    {
        Log::debug('Now in download()', ['job' => $job->key]);
        $config = $this->repository->getConfiguration($job);
        // This is CSV import specific:
        $config['delimiter'] = $config['delimiter'] ?? ',';
        $config['delimiter'] = "\t" === $config['delimiter'] ? 'tab' : $config['delimiter'];

        $result = json_encode($config, JSON_PRETTY_PRINT);
        $name   = sprintf('"%s"', addcslashes('import-configuration-' . date('Y-m-d') . '.json', '"\\'));
        /** @var LaravelResponse $response */
        $response = response($result);
        $response->header('Content-disposition', 'attachment; filename=' . $name)
                 ->header('Content-Type', 'application/json')
                 ->header('Content-Description', 'File Transfer')
                 ->header('Connection', 'Keep-Alive')
                 ->header('Expires', '0')
                 ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                 ->header('Pragma', 'public')
                 ->header('Content-Length', strlen($result));

        return $response;
    }

    /**
     * General import index.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $providers    = $this->providers;
        $subTitle     = (string)trans('import.index_breadcrumb');
        $subTitleIcon = 'fa-home';
        $isDemoUser   = $this->userRepository->hasRole(auth()->user(), 'demo');

        return view('import.index', compact('subTitle', 'subTitleIcon', 'providers', 'isDemoUser'));
    }
}
