<?php

namespace FireflyIII\Http\Controllers;

use Crypt;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\ImportUploadRequest;
use FireflyIII\Import\Importer\ImporterInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use SplFileObject;
use Storage;
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
     * @param ImportJob $job
     *
     * @return View
     */
    public function configure(ImportJob $job)
    {
        // create proper importer (depends on job)
        $type = $job->file_type;
        /** @var ImporterInterface $importer */
        $importer = app('FireflyIII\Import\Importer\\' . ucfirst($type) . 'Importer');
        $importer->setJob($job);
        $importer->configure();
        $data = $importer->getConfigurationData();

        return view('import.' . $type . '.configure', compact('data', 'job'));


    }

    /**
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
     * @param ImportUploadRequest          $request
     * @param ImportJobRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function upload(ImportUploadRequest $request, ImportJobRepositoryInterface $repository)
    {
        // create import job:
        $type             = $request->get('import_file_type');
        $job              = $repository->create($type);
        $upload           = $request->files->get('import_file');
        $newName          = $job->key . '.upload';
        $uploaded         = new SplFileObject($upload->getRealPath());
        $content          = $uploaded->fread($uploaded->getSize());
        $contentEncrypted = Crypt::encrypt($content);
        $disk             = Storage::disk('upload');
        $disk->put($newName, $contentEncrypted);

        return redirect(route('import.configure', [$job->key]));

    }
}
