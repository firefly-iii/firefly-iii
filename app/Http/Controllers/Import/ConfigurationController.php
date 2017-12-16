<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Import;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Import\Configuration\ConfiguratorInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;

/**
 * Class ConfigurationController
 */
class ConfigurationController extends Controller
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
     * Configure the job. This method is returned to until job is deemed "configured".
     *
     * @param ImportJob $job
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     * @throws FireflyException
     */
    public function index(ImportJob $job)
    {
        // create configuration class:
        $configurator = $this->makeConfigurator($job);

        // is the job already configured?
        if ($configurator->isJobConfigured()) {
            $this->repository->updateStatus($job, 'configured');

            return redirect(route('import.file.status', [$job->key]));
        }
        $view         = $configurator->getNextView();
        $data         = $configurator->getNextData();
        $subTitle     = trans('firefly.import_config_bread_crumb');
        $subTitleIcon = 'fa-wrench';

        return view($view, compact('data', 'job', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param ImportJob $job
     *
     * @return ConfiguratorInterface
     *
     * @throws FireflyException
     */
    private function makeConfigurator(ImportJob $job): ConfiguratorInterface
    {
        $type      = $job->file_type;
        $key       = sprintf('import.configuration.%s', $type);
        $className = config($key);
        if (null === $className || !class_exists($className)) {
            throw new FireflyException(sprintf('Cannot find configurator class for job of type "%s".',$type)); // @codeCoverageIgnore
        }
        /** @var ConfiguratorInterface $configurator */
        $configurator = app($className);
        $configurator->setJob($job);

        return $configurator;
    }
}