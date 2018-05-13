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
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use View;

/**
 * Class FileController.
 */
class IndexController extends Controller
{
    /** @var ImportJobRepositoryInterface */
    public $repository;

    /** @var UserRepositoryInterface */
    public $userRepository;

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
                $this->repository     = app(ImportJobRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);

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
        Log::debug(sprintf('Will create job for provider %s', $importProvider));
        // can only create "fake" for demo user.
        $providers = array_keys($this->getProviders());
        if (!\in_array($importProvider, $providers, true)) {
            Log::error(sprintf('%s-provider is disabled. Cannot create job.', $importProvider));
            session()->flash('warning', trans('import.cannot_create_for_provider', ['provider' => $importProvider]));

            return redirect(route('import.index'));
        }

        $importJob = $this->repository->create($importProvider);
        Log::debug(sprintf('Created job #%d for provider %s', $importJob->id, $importProvider));

        $hasPreReq = (bool)config(sprintf('import.has_prereq.%s', $importProvider));
        $hasConfig = (bool)config(sprintf('import.has_config.%s', $importProvider));
        // if job provider has no prerequisites:
        if ($hasPreReq === false) {
            Log::debug('Provider has no prerequisites. Continue.');
            // @codeCoverageIgnoreStart
            // if job provider also has no configuration:
            if ($hasConfig === false) {
                Log::debug('Provider has no configuration. Job is ready to start.');
                $this->repository->updateStatus($importJob, 'ready_to_run');
                Log::debug('Redirect to status-page.');

                return redirect(route('import.job.status.index', [$importJob->key]));
            }

            // update job to say "has_prereq".
            $this->repository->setStatus($importJob, 'has_prereq');

            // redirect to job configuration.
            Log::debug('Redirect to configuration.');

            return redirect(route('import.job.configuration.index', [$importJob->key]));
            // @codeCoverageIgnoreEnd
        }
        Log::debug('Job provider has prerequisites.');
        // if need to set prerequisites, do that first.
        $class = (string)config(sprintf('import.prerequisites.%s', $importProvider));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('No class to handle configuration for "%s".', $importProvider)); // @codeCoverageIgnore
        }
        /** @var PrerequisitesInterface $providerPre */
        $providerPre = app($class);
        $providerPre->setUser(auth()->user());

        if (!$providerPre->isComplete()) {
            Log::debug('Job provider prerequisites are not yet filled in. Redirect to prerequisites-page.');

            // redirect to global prerequisites
            return redirect(route('import.prerequisites.index', [$importProvider, $importJob->key]));
        }
        Log::debug('Prerequisites are complete.');

        // update job to say "has_prereq".
        $this->repository->setStatus($importJob, 'has_prereq');
        if ($hasConfig === false) {
            Log::debug('Provider has no configuration. Job is ready to start.');
            $this->repository->updateStatus($importJob, 'ready_to_run');
            Log::debug('Redirect to status-page.');

            return redirect(route('import.job.status.index', [$importJob->key]));
        }
        Log::debug('Job has configuration. Redirect to job-config.');
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
        $providers    = $this->getProviders();
        $subTitle     = trans('import.index_breadcrumb');
        $subTitleIcon = 'fa-home';

        return view('import.index', compact('subTitle', 'subTitleIcon', 'providers'));
    }

    /**
     * @return array
     */
    private function getProviders(): array
    {
        // get and filter all import routines:
        /** @var array $config */
        $providerNames = array_keys(config('import.enabled'));
        $providers     = [];
        $isDemoUser    = $this->userRepository->hasRole(auth()->user(), 'demo');
        $isDebug       = (bool)config('app.debug');
        foreach ($providerNames as $providerName) {
            //Log::debug(sprintf('Now with provider %s', $providerName));
            // only consider enabled providers
            $enabled        = (bool)config(sprintf('import.enabled.%s', $providerName));
            $allowedForDemo = (bool)config(sprintf('import.allowed_for_demo.%s', $providerName));
            $allowedForUser = (bool)config(sprintf('import.allowed_for_user.%s', $providerName));
            if ($enabled === false) {
                //Log::debug('Provider is not enabled. NEXT!');
                continue;
            }

            if ($isDemoUser === true && $allowedForDemo === false) {
                //Log::debug('User is demo and this provider is not allowed for demo user. NEXT!');
                continue;
            }
            if ($isDemoUser === false && $allowedForUser === false && $isDebug === false) {
                //Log::debug('User is not demo and this provider is not allowed for such users. NEXT!');
                continue;
            }

            $providers[$providerName] = [
                'has_prereq' => (bool)config('import.has_prereq.' . $providerName),
                'has_config' => (bool)config('import.has_config.' . $providerName),
            ];
            $class                    = (string)config(sprintf('import.prerequisites.%s', $providerName));
            $result                   = false;
            if ($class !== '' && class_exists($class)) {
                //Log::debug('Will not check prerequisites.');
                /** @var PrerequisitesInterface $object */
                $object = app($class);
                $object->setUser(auth()->user());
                $result = $object->isComplete();
            }
            $providers[$providerName]['prereq_complete'] = $result;
        }
        Log::debug(sprintf('Enabled providers: %s', json_encode(array_keys($providers))));

        return $providers;
    }
}
