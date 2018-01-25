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
use Illuminate\Http\Request;
use Log;

/**
 * Class PrerequisitesController
 */
class PrerequisitesController extends Controller
{

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

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class);
    }

    /**
     * Once there are no prerequisites, this method will create an importjob object and
     * redirect the user to a view where this object can be used by a bank specific
     * class to process.
     *
     * @param string $bank
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     *
     * @throws FireflyException
     */
    public function index(string $bank)
    {
        if (true === !(config(sprintf('import.enabled.%s', $bank)))) {
            throw new FireflyException(sprintf('Cannot import from "%s" at this time.', $bank)); // @codeCoverageIgnore
        }
        $class = strval(config(sprintf('import.prerequisites.%s', $bank)));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('No class to handle "%s".', $bank)); // @codeCoverageIgnore
        }

        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());

        if ($object->hasPrerequisites()) {
            $view       = $object->getView();
            $parameters = ['title' => strval(trans('firefly.import_index_title')), 'mainTitleIcon' => 'fa-archive'];
            $parameters = array_merge($object->getViewParameters(), $parameters);

            return view($view, $parameters);
        }

        // if no (more) prerequisites, return to create a job:
        return redirect(route('import.create-job', [$bank]));
    }

    /**
     * This method processes the prerequisites the user has entered in the previous step.
     *
     * Whatever storePrerequisites does, it should make sure that the system is ready to continue immediately. So
     * no extra calls or stuff, except maybe to open a session
     *
     * @see PrerequisitesInterface::storePrerequisites
     *
     * @param Request $request
     * @param string  $bank
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function post(Request $request, string $bank)
    {
        Log::debug(sprintf('Now in postPrerequisites for %s', $bank));

        if (true === !(config(sprintf('import.enabled.%s', $bank)))) {
            throw new FireflyException(sprintf('Cannot import from "%s" at this time.', $bank)); // @codeCoverageIgnore
        }

        $class = strval(config(sprintf('import.prerequisites.%s', $bank)));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Cannot find class %s', $class)); // @codeCoverageIgnore
        }
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());
        if (!$object->hasPrerequisites()) {
            Log::debug(sprintf('No more prerequisites for %s, move to form.', $bank));

            return redirect(route('import.create-job', [$bank]));
        }
        Log::debug('Going to store entered prerequisites.');
        // store post data
        $result = $object->storePrerequisites($request);

        if ($result->count() > 0) {
            $request->session()->flash('error', $result->first());
        }

        return redirect(route('import.prerequisites', [$bank]));
    }
}
