<?php

namespace FireflyIII\Http\Controllers;

use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\ImportUploadRequest;
use FireflyIII\Import\Importer\ImporterInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\Request;
use Log;
use SplFileObject;
use Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use View;

/**
 * Class ImportController
 *
 * @package FireflyIII\Http\Controllers
 */
class ImportController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('mainTitleIcon', 'fa-archive');
        View::share('title', trans('firefly.import_data'));
    }

    /**
     * This is step 3.
     * This is the first step in configuring the job. It can only be executed
     * when the job is set to "import_status_never_started".
     *
     * @param ImportJob $job
     *
     * @return View
     * @throws FireflyException
     */
    public function configure(ImportJob $job)
    {
        if (!$this->jobInCorrectStep($job, 'configure')) {
            return $this->redirectToCorrectStep($job);
        }

        // actual code
        $importer = $this->makeImporter($job);
        $importer->configure();
        $data         = $importer->getConfigurationData();
        $subTitle     = trans('firefly.configure_import');
        $subTitleIcon = 'fa-wrench';

        return view('import.' . $job->file_type . '.configure', compact('data', 'job', 'subTitle', 'subTitleIcon'));


    }

    /**
     * This is step 1. Upload a file.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $subTitle          = trans('firefly.import_data_index');
        $subTitleIcon      = 'fa-home';
        $importFileTypes   = [];
        $defaultImportType = config('firefly.default_import_format');

        foreach (array_keys(config('firefly.import_formats')) as $type) {
            $importFileTypes[$type] = trans('firefly.import_file_type_' . $type);
        }

        return view('import.index', compact('subTitle', 'subTitleIcon', 'importFileTypes', 'defaultImportType'));
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
        if (!$this->jobInCorrectStep($job, 'process')) {
            return $this->redirectToCorrectStep($job);
        }

        // actual code
        $importer = $this->makeImporter($job);
        $data     = $request->all();
        $files    = $request->files;
        $importer->saveImportConfiguration($data, $files);

        // update job:
        $job->status = 'import_configuration_saved';
        $job->save();

        // return redirect to settings.
        // this could loop until the user is done.
        return redirect(route('import.settings', $job->key));
    }

    /**
     * This step 6. Depending on the importer, this will process the
     * settings given and store them.
     *
     * @param Request   $request
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    public function postSettings(Request $request, ImportJob $job)
    {
        if (!$this->jobInCorrectStep($job, 'store-settings')) {
            return $this->redirectToCorrectStep($job);
        }
        $importer = $this->makeImporter($job);
        $importer->storeSettings($request);

        // return redirect to settings (for more settings perhaps)
        return redirect(route('import.settings', [$job->key]));
    }

    /**
     * Step 5. Depending on the importer, this will show the user settings to
     * fill in.
     *
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    public function settings(ImportJob $job)
    {
        if (!$this->jobInCorrectStep($job, 'settings')) {
            return $this->redirectToCorrectStep($job);
        }
        $importer     = $this->makeImporter($job);
        $subTitle     = trans('firefy.settings_for_import');
        $subTitleIcon = 'fa-wrench';

        // now show settings screen to user.
        if ($importer->requireUserSettings()) {
            $data = $importer->getDataForSettings();
            $view = $importer->getViewForSettings();

            return view($view, compact('data', 'job', 'subTitle', 'subTitleIcon'));
        }

        // if no more settings, save job and continue to process thing.


        echo 'now in settings (done)';
        exit;

        // actual code


        // ask the importer for the requested action.
        // for example pick columns or map data.
        // depends of course on the data in the job.
    }

    /**
     * This is step 2. It creates an Import Job. Stores the import.
     *
     * @param ImportUploadRequest          $request
     * @param ImportJobRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function upload(ImportUploadRequest $request, ImportJobRepositoryInterface $repository)
    {
        // create import job:
        $type = $request->get('import_file_type');
        $job  = $repository->create($type);
        Log::debug('Created new job', ['key' => $job->key, 'id' => $job->id]);

        /** @var UploadedFile $upload */
        $upload           = $request->files->get('import_file');
        $newName          = $job->key . '.upload';
        $uploaded         = new SplFileObject($upload->getRealPath());
        $content          = $uploaded->fread($uploaded->getSize());
        $contentEncrypted = Crypt::encrypt($content);
        $disk             = Storage::disk('upload');
        $disk->put($newName, $contentEncrypted);

        Log::debug('Uploaded file', ['name' => $upload->getClientOriginalName(), 'size' => $upload->getSize(), 'mime' => $upload->getClientMimeType()]);

        // store configuration file's content into the job's configuration
        // thing.
        // otherwise, leave it empty.
        if ($request->files->has('configuration_file')) {
            /** @var UploadedFile $configFile */
            $configFile = $request->files->get('configuration_file');
            Log::debug(
                'Uploaded configuration file',
                ['name' => $configFile->getClientOriginalName(), 'size' => $configFile->getSize(), 'mime' => $configFile->getClientMimeType()]
            );

            $configFileObject = new SplFileObject($configFile->getRealPath());
            $configRaw        = $configFileObject->fread($configFileObject->getSize());
            $configuration    = json_decode($configRaw, true);

            if (!is_null($configuration) && is_array($configuration)) {
                Log::debug('Found configuration', $configuration);
                $job->configuration = $configuration;
                $job->save();
            }
        }

        return redirect(route('import.configure', [$job->key]));

    }

    /**
     * @param ImportJob $job
     * @param string    $method
     *
     * @return bool
     */
    private function jobInCorrectStep(ImportJob $job, string $method): bool
    {
        switch ($method) {
            case 'configure':
            case 'process':
                return $job->status === 'import_status_never_started';
                break;
            case 'settings':
            case 'store-settings':
                return $job->status === 'import_configuration_saved';
                break;
        }

        return false;

    }

    /**
     * @param ImportJob $job
     *
     * @return ImporterInterface
     */
    private function makeImporter(ImportJob $job): ImporterInterface
    {
        // create proper importer (depends on job)
        $type = $job->file_type;
        /** @var ImporterInterface $importer */
        $importer = app('FireflyIII\Import\Importer\\' . ucfirst($type) . 'Importer');
        $importer->setJob($job);

        return $importer;

    }

    /**
     * @param ImportJob $job
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    private function redirectToCorrectStep(ImportJob $job)
    {
        switch ($job->status) {
            case 'import_status_never_started':
                return redirect(route('import.configure', [$job->key]));
                break;
            case 'import_configuration_saved':
                return redirect(route('import.settings', [$job->key]));
                break;
        }

        throw new FireflyException('Cannot redirect for job state ' . $job->status);

    }
}
