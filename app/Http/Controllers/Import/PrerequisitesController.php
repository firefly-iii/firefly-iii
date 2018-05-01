<?php
/**
 * PrerequisitesController.php
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
use FireflyIII\Import\Prerequisites\PrerequisitesInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\Request;
use Log;

/**
 * Class PrerequisitesController
 */
class PrerequisitesController extends Controller
{

    /** @var ImportJobRepositoryInterface */
    private $repository;

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

                app('view')->share('subTitleIcon', 'fa-check');

                $this->repository = app(ImportJobRepositoryInterface::class);

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class);
    }

    /**
     * This method will process and store import provider global prerequisites
     * such as API keys.
     *
     * @param string    $importProvider
     * @param ImportJob $importJob
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws FireflyException
     */
    public function index(string $importProvider, ImportJob $importJob = null)
    {
        // catch impossible status:
        $allowed = ['new'];
        if (null !== $importJob && !in_array($importJob->status, $allowed)) {
            Log::error('Job is not new but wants to do prerequisites');
            session()->flash('error', trans('import.bad_job_status'));
            return redirect(route('import.index'));
        }

        app('view')->share('subTitle', trans('import.prerequisites_breadcrumb_' . $importProvider));
        $class = (string)config(sprintf('import.prerequisites.%s', $importProvider));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('No class to handle configuration for "%s".', $importProvider)); // @codeCoverageIgnore
        }
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());

        if (null !== $importJob && $object->isComplete()) {
            // update job:
            $this->repository->setStatus($importJob, 'has_prereq');

            // redirect to job config:
            return redirect(route('import.job.configuration.index', [$importJob->key]));
        }


        $view       = $object->getView();
        $parameters = ['title' => (string)trans('firefly.import_index_title'), 'mainTitleIcon' => 'fa-archive', 'importJob' => $importJob];
        $parameters = array_merge($object->getViewParameters(), $parameters);

        return view($view, $parameters);
    }

    /**
     * This method processes the prerequisites the user has entered in the previous step.
     *
     * Whatever storePrerequisites does, it should make sure that the system is ready to continue immediately. So
     * no extra calls or stuff, except maybe to open a session
     *
     * @see PrerequisitesInterface::storePrerequisites
     *
     * @param Request   $request
     * @param string    $importProvider
     * @param ImportJob $importJob
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function post(Request $request, string $importProvider, ImportJob $importJob = null)
    {
        Log::debug(sprintf('Now in postPrerequisites for %s', $importProvider));

        // catch impossible status:
        $allowed = ['new'];
        if (null !== $importJob && !in_array($importJob->status, $allowed)) {
            Log::error('Job is not new but wants to do prerequisites');
            session()->flash('error', trans('import.bad_job_status'));
            return redirect(route('import.index'));
        }


        $class = (string)config(sprintf('import.prerequisites.%s', $importProvider));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Cannot find class %s', $class)); // @codeCoverageIgnore
        }
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());
        Log::debug('Going to store entered prerequisites.');
        // store post data
        $result = $object->storePrerequisites($request);
        Log::debug(sprintf('Result of storePrerequisites has message count: %d', $result->count()));

        if ($result->count() > 0) {
            $request->session()->flash('error', $result->first());

            // redirect back:
            return redirect(route('import.prerequisites.index', [$importProvider, $importJob->key]))->withInput();
        }

        // session flash!
        $request->session()->flash('success', (string)trans('import.prerequisites_saved_for_' . $importProvider));

        // if has job, redirect to global config for provider
        // if no job, back to index!
        if (null === $importJob) {
            return redirect(route('import.index'));
        }

        // update job:
        $this->repository->setStatus($importJob, 'has_prereq');

        // redirect to job config:
        return redirect(route('import.job.configuration.index', [$importJob->key]));


    }
}
