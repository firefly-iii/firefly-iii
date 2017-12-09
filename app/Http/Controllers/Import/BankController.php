<?php
/**
 * BankController.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Prerequisites\PrerequisitesInterface;
use Illuminate\Http\Request;
use Log;
use Session;

class BankController extends Controller
{

    /**
     * Once there are no prerequisites, this method will create an importjob object and
     * redirect the user to a view where this object can be used by a bank specific
     * class to process.
     *
     * @param ImportJobRepositoryInterface $repository
     * @param string                       $bank
     *
     * @return \Illuminate\Http\RedirectResponse|null
     * @throws FireflyException
     */
    public function createJob(ImportJobRepositoryInterface $repository, string $bank)
    {
        $class = config(sprintf('firefly.import_pre.%s', $bank));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Cannot find class %s', $class));
        }
        $importJob = $repository->create($bank);

        return redirect(route('import.file.configure', [$importJob->key]));
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
     * @throws FireflyException
     */
    public function postPrerequisites(Request $request, string $bank)
    {
        Log::debug(sprintf('Now in postPrerequisites for %s', $bank));
        $class = config(sprintf('firefly.import_pre.%s', $bank));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Cannot find class %s', $class));
        }
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());
        if (!$object->hasPrerequisites()) {
            Log::debug(sprintf('No more prerequisites for %s, move to form.', $bank));

            return redirect(route('import.bank.create-job', [$bank]));
        }
        Log::debug('Going to store entered preprerequisites.');
        // store post data
        $result = $object->storePrerequisites($request);

        if ($result->count() > 0) {
            Session::flash('error', $result->first());

            return redirect(route('import.bank.prerequisites', [$bank]));
        }

        return redirect(route('import.bank.create-job', [$bank]));
    }

    /**
     * This method shows you, if necessary, a form that allows you to enter any required values, such as API keys,
     * login passwords or other values.
     *
     * @param string $bank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     * @throws FireflyException
     */
    public function prerequisites(string $bank)
    {
        $class = config(sprintf('firefly.import_pre.%s', $bank));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Cannot find class %s', $class));
        }
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());

        if ($object->hasPrerequisites()) {
            $view       = $object->getView();
            $parameters = ['title' => strval(trans('firefly.import_index_title')), 'mainTitleIcon' => 'fa-archive'];
            $parameters = $object->getViewParameters() + $parameters;

            return view($view, $parameters);
        }

        return redirect(route('import.bank.create-job', [$bank]));
    }
}
