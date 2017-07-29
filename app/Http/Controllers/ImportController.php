<?php
/**
 * ImportController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Requests\ImportUploadRequest;
use FireflyIII\Import\Configurator\ConfiguratorInterface;
use FireflyIII\Import\Routine\ImportRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Log;
use Response;
use Session;
use View;

/**
 * Class ImportController.
 *
 * @package FireflyIII\Http\Controllers
 */
class ImportController extends Controller
{
    /** @var  ImportJobRepositoryInterface */
    public $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                View::share('mainTitleIcon', 'fa-archive');
                View::share('title', trans('firefly.import_index_title'));
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
        $config['delimiter']               = $config['delimiter'] === "\t" ? 'tab' : $config['delimiter'];

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

        return view('import.index', compact('subTitle', 'subTitleIcon', 'importFileTypes', 'defaultImportType'));
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

        return redirect(route('import.configure', [$job->key]));
    }

    /**
     *
     * Show status of import job in JSON.
     *
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function json(ImportJob $job)
    {
        $result = [
            'started'         => false,
            'finished'        => false,
            'running'         => false,
            'errors'          => array_values($job->extended_status['errors']),
            'percentage'      => 0,
            'show_percentage' => false,
            'steps'           => $job->extended_status['steps'],
            'done'            => $job->extended_status['done'],
            'statusText'      => trans('firefly.import_status_job_' . $job->status),
            'status'          => $job->status,
            'finishedText'    => '',
        ];

        if ($job->extended_status['steps'] !== 0) {
            $result['percentage']      = round(($job->extended_status['done'] / $job->extended_status['steps']) * 100, 0);
            $result['show_percentage'] = true;
        }

        if ($job->status === 'finished') {
            $tagId = $job->extended_status['tag'];
            /** @var TagRepositoryInterface $repository */
            $repository             = app(TagRepositoryInterface::class);
            $tag                    = $repository->find($tagId);
            $result['finished']     = true;
            $result['finishedText'] = trans('firefly.import_status_finished_job', ['link' => route('tags.show', [$tag->id, 'all']), 'tag' => $tag->tag]);
        }

        if ($job->status === 'running') {
            $result['started'] = true;
            $result['running'] = true;
        }

        return Response::json($result);
    }

    /**
     * Step 4. Save the configuration.
     *
     * @param Request   $request
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
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

        if(strlen($warning) > 0) {
            Session::flash('warning', $warning);
        }

        // return to configure
        return redirect(route('import.configure', [$job->key]));
    }

    /**
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\JsonResponse
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

        throw new FireflyException('Job did not complete succesfully.');
    }

    /**
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function status(ImportJob $job)
    {
        $statuses = ['configured', 'running', 'finished'];
        if (!in_array($job->status, $statuses)) {
            return redirect(route('import.configure', [$job->key]));
        }
        $subTitle     = trans('firefly.import_status_sub_title');
        $subTitleIcon = 'fa-star';

        return view('import.status', compact('job', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param ImportJob $job
     *
     * @return ConfiguratorInterface
     * @throws FireflyException
     */
    private function makeConfigurator(ImportJob $job): ConfiguratorInterface
    {
        $type      = $job->file_type;
        $key       = sprintf('firefly.import_configurators.%s', $type);
        $className = config($key);
        if (is_null($className)) {
            throw new FireflyException('Cannot find configurator class for this job.'); // @codeCoverageIgnore
        }
        /** @var ConfiguratorInterface $configurator */
        $configurator = app($className);
        $configurator->setJob($job);


        return $configurator;
    }
}
