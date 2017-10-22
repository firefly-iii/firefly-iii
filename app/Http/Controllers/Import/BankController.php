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


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Import\Information\InformationInterface;
use FireflyIII\Support\Import\Prerequisites\PrerequisitesInterface;
use Illuminate\Http\Request;
use Log;
use Session;

class BankController extends Controller
{

    /**
     * This method must ask the user all parameters necessary to start importing data. This may not be enough
     * to finish the import itself (ie. mapping) but it should be enough to begin: accounts to import from,
     * accounts to import into, data ranges, etc.
     *
     * @param string $bank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function form(string $bank)
    {
        $class = config(sprintf('firefly.import_pre.%s', $bank));
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());

        if ($object->hasPrerequisites()) {
            return redirect(route('import.bank.prerequisites', [$bank]));
        }
        $class = config(sprintf('firefly.import_info.%s', $bank));
        /** @var InformationInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());
        $remoteAccounts = $object->getAccounts();

        return view('import.bank.form', compact('remoteAccounts', 'bank'));

    }

    /**
     * With the information given in the submitted form Firefly III will call upon the bank's classes to return transaction
     * information as requested. The user will be able to map unknown data and continue. Or maybe, it's put into some kind of
     * fake CSV file and forwarded to the import routine.
     *
     * @param Request $request
     * @param string  $bank
     *
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function postForm(Request $request, string $bank)
    {

        $class = config(sprintf('firefly.import_pre.%s', $bank));
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());

        if ($object->hasPrerequisites()) {
            return redirect(route('import.bank.prerequisites', [$bank]));
        }
        $remoteAccounts = $request->get('do_import');
        if (!is_array($remoteAccounts) || count($remoteAccounts) === 0) {
            Session::flash('error', 'Must select accounts');

            return redirect(route('import.bank.form', [$bank]));
        }
        $remoteAccounts = array_keys($remoteAccounts);
        $class          = config(sprintf('firefly.import_pre.%s', $bank));
        // get import file

        // get import config


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
     */
    public function postPrerequisites(Request $request, string $bank)
    {
        Log::debug(sprintf('Now in postPrerequisites for %s', $bank));
        $class = config(sprintf('firefly.import_pre.%s', $bank));
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());
        if (!$object->hasPrerequisites()) {
            Log::debug(sprintf('No more prerequisites for %s, move to form.', $bank));

            return redirect(route('import.bank.form', [$bank]));
        }
        Log::debug('Going to store entered preprerequisites.');
        // store post data
        $result = $object->storePrerequisites($request);

        if ($result->count() > 0) {
            Session::flash('error', $result->first());

            return redirect(route('import.bank.prerequisites', [$bank]));
        }

        return redirect(route('import.bank.form', [$bank]));
    }

    /**
     * This method shows you, if necessary, a form that allows you to enter any required values, such as API keys,
     * login passwords or other values.
     *
     * @param string $bank
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function prerequisites(string $bank)
    {
        $class = config(sprintf('firefly.import_pre.%s', $bank));
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());

        if ($object->hasPrerequisites()) {
            $view       = $object->getView();
            $parameters = $object->getViewParameters();

            return view($view, $parameters);
        }

        return redirect(route('import.bank.form', [$bank]));
    }

}
