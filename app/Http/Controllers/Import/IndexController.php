<?php
declare(strict_types=1);


namespace FireflyIII\Http\Controllers\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use View;

/**
 * Class FileController.
 */
class IndexController extends Controller
{
    /** @var ImportJobRepositoryInterface */
    public $repository;

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
                $this->repository = app(ImportJobRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Creates a new import job for $bank with the default (global) job configuration.
     *
     * @param string $bank
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    public function create(string $bank)
    {
        if (!(config(sprintf('import.enabled.%s', $bank))) === true) {
            throw new FireflyException(sprintf('Cannot import from "%s" at this time.', $bank));
        }

        $importJob = $this->repository->create($bank);

        // from here, always go to configure step.
        return redirect(route('import.configure', [$importJob->key]));

    }

    /**
     * General import index.
     *
     * @return View
     */
    public function index()
    {
        $subTitle     = trans('firefly.import_index_sub_title');
        $subTitleIcon = 'fa-home';
        $routines     = config('import.enabled');

        return view('import.index', compact('subTitle', 'subTitleIcon', 'routines'));
    }

}