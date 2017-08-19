<?php
/**
 * BankController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
     */
    public function form(string $bank)
    {
        $class = config(sprintf('firefly.import_pre.%s', $bank));
        /** @var PrerequisitesInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());

        if ($object->hasPrerequisites()) {
            return redirect(route('import.banq.prerequisites', [$bank]));
        }
        $class = config(sprintf('firefly.import_info.%s', $bank));
        /** @var InformationInterface $object */
        $object = app($class);
        $object->setUser(auth()->user());
        $remoteAccounts = $object->getAccounts();

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
