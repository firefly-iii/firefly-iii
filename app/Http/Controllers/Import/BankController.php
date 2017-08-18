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
use FireflyIII\Support\Import\Prerequisites\PrerequisitesInterface;
use Illuminate\Http\Request;
use Log;
use Session;

class BankController extends Controller
{
    /**
     *
     */
    public function form()
    {


    }

    /**
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

        if (!$object->hasPrerequisites()) {
            return redirect(route('import.bank.form', [$bank]));
        }
    }

}
