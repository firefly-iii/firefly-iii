<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Import;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Import\Prerequisites\PrerequisitesInterface;
use Illuminate\Http\Request;
use Log;

/**
 * Class PrerequisitesController
 */
class PrerequisitesController extends Controller
{
    /**
     * Once there are no prerequisites, this method will create an importjob object and
     * redirect the user to a view where this object can be used by a bank specific
     * class to process.
     *
     * @param string $bank
     *
     * @return \Illuminate\Http\RedirectResponse|null
     * @throws FireflyException
     */
    public function index(string $bank)
    {
        if (!(config(sprintf('import.enabled.%s', $bank))) === true) {
            throw new FireflyException(sprintf('Cannot import from "%s" at this time.', $bank));
        }
        $class = strval(config(sprintf('import.prerequisites.%s', $bank)));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('No class to handle "%s".', $bank));
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

            return redirect(route('import.create-job', [$bank]));
        }
        Log::debug('Going to store entered prerequisites.');
        // store post data
        $result = $object->storePrerequisites($request);

        if ($result->count() > 0) {
            $request->session()->flash('error', $result->first());

            return redirect(route('import.prerequisites', [$bank]));
        }

        return redirect(route('import.create-job', [$bank]));
    }


}