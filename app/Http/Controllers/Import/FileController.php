<?php
/**
 * FileController.php
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
use FireflyIII\Http\Requests\ImportUploadRequest;
use FireflyIII\Import\Configurator\ConfiguratorInterface;
use FireflyIII\Import\Routine\ImportRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Log;
use Response;
use Session;
use View;

/**
 * Class FileController.
 */
class FileController extends Controller
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
     * This is step 3. This repeats until the job is configured.
     *
     * @param ImportJob $job
     *
     * @return View
     *
     * @throws FireflyException
     */
    public function configure(ImportJob $job)
    {
        // create configuration class:
        $configurator = $this->makeConfigurator($job);

        // is the job already configured?
        if ($configurator->isJobConfigured()) {
            $this->repository->updateStatus($job, 'configured');

            return redirect(route('import.status', [$job->key]));
        }
        $view         = $configurator->getNextView();
        $data         = $configurator->getNextData();
        $subTitle     = trans('firefly.import_config_bread_crumb');
        $subTitleIcon = 'fa-wrench';

        return view($view, compact('data', 'job', 'subTitle', 'subTitleIcon'));
    }

    /**
     * Generate a JSON file of the job's configuration and send it to the user.
     *
     * @param ImportJob $job
     *
     * @return LaravelResponse
     */
    public function download(ImportJob $job)
    {
        Log::debug('Now in download()', ['job' => $job->key]);
        $config = $job->configuration;

        // This is CSV import specific:
        $config['column-roles-complete']   = false;
        $config['column-mapping-complete'] = false;
        $config['initial-config-complete'] = false;
        $config['has-file-upload']         = false;
        $config['delimiter']               = "\t" === $config['delimiter'] ? 'tab' : $config['delimiter'];

        $result = json_encode($config, JSON_PRETTY_PRINT);
        $name   = sprintf('"%s"', addcslashes('import-configuration-' . date('Y-m-d') . '.json', '"\\'));

        /** @var LaravelResponse $response */
        $response = response($result, 200);
        $response->header('Content-disposition', 'attachment; filename=' . $name)
                 ->header('Content-Type', 'application/json')
                 ->header('Content-Description', 'File Transfer')
                 ->header('Connection', 'Keep-Alive')
                 ->header('Expires', '0')
                 ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                 ->header('Pragma', 'public')
                 ->header('Content-Length', strlen($result));

        return $response;
    }

    /**
     * This is step 1. Upload a file.
     *
     * @return View
     */
    public function index()
    {
        $subTitle          = trans('firefly.import_index_sub_title');
        $subTitleIcon      = 'fa-home';
        $importFileTypes   = [];
        $defaultImportType = config('firefly.default_import_format');

        foreach (array_keys(config('firefly.import_formats')) as $type) {
            $importFileTypes[$type] = trans('firefly.import_file_type_' . $type);
        }

        return view('import.file.index', compact('subTitle', 'subTitleIcon', 'importFileTypes', 'defaultImportType'));
    }

    /**
     * This is step 2. It creates an Import Job. Stores the import.
     *
     * @param ImportUploadRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function initialize(ImportUploadRequest $request)
    {
        Log::debug('Now in initialize()');

        // create import job:
        $type = $request->get('import_file_type');
        $job  = $this->repository->create($type);
        Log::debug('Created new job', ['key' => $job->key, 'id' => $job->id]);

        // process file:
        $this->repository->processFile($job, $request->files->get('import_file'));

        // process config, if present:
        if ($request->files->has('configuration_file')) {
            $this->repository->processConfiguration($job, $request->files->get('configuration_file'));
        }

        $this->repository->updateStatus($job, 'initialized');

        return redirect(route('import.file.configure', [$job->key]));
    }


    /**
     * Step 4. Save the configuration.
     *
     * @param Request   $request
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    public function postConfigure(Request $request, ImportJob $job)
    {
        Log::debug('Now in postConfigure()', ['job' => $job->key]);
        $configurator = $this->makeConfigurator($job);

        // is the job already configured?
        if ($configurator->isJobConfigured()) {
            return redirect(route('import.status', [$job->key]));
        }
        $data = $request->all();
        $configurator->configureJob($data);

        // get possible warning from configurator:
        $warning = $configurator->getWarningMessage();

        if (strlen($warning) > 0) {
            Session::flash('warning', $warning);
        }

        // return to configure
        return redirect(route('import.configure', [$job->key]));
    }

    /**
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws FireflyException
     */
    public function start(ImportJob $job)
    {
        /** @var ImportRoutine $routine */
        $routine = app(ImportRoutine::class);
        $routine->setJob($job);
        $result = $routine->run();
        if ($result) {
            return Response::json(['run' => 'ok']);
        }

        throw new FireflyException('Job did not complete successfully.');
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
        $key       = sprintf('firefly.import_configurators.%s', $type);
        $className = config($key);
        if (null === $className) {
            throw new FireflyException('Cannot find configurator class for this job.'); // @codeCoverageIgnore
        }
        /** @var ConfiguratorInterface $configurator */
        $configurator = app($className);
        $configurator->setJob($job);

        return $configurator;
    }
}
