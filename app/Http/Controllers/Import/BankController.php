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

class BankController extends Controller
{

    public function postPrerequisites()
    {

    }

    /**
     * @param string $bank
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
            echo 'redirect to import form.';
        }

    }

}