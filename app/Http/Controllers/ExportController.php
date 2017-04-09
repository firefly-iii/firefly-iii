<?php
/**
 * ExportController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Http\Requests\ExportFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ExportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use Illuminate\Http\Response as LaravelResponse;
use Preferences;
use Response;
use View;

/**
 * Class ExportController
 *
 * @package FireflyIII\Http\Controllers
 */
class ExportController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('mainTitleIcon', 'fa-file-archive-o');
                View::share('title', trans('firefly.export_data'));

                return $next($request);
            }
        );
    }

    /**
     * @param ExportJobRepositoryInterface $repository
     * @param ExportJob                    $job
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws FireflyException
     */
    public function download(ExportJobRepositoryInterface $repository, ExportJob $job)
    {
        $file   = $job->key . '.zip';
        $date   = date('Y-m-d \a\t H-i-s');
        $name   = 'Export job on ' . $date . '.zip';
        $quoted = sprintf('"%s"', addcslashes($name, '"\\'));

        if (!$repository->exists($job)) {
            throw new FireflyException('Against all expectations, zip file "' . $file . '" does not exist.');
        }
        $content = $repository->getContent($job);


        $job->change('export_downloaded');
        /** @var LaravelResponse $response */
        $response = response($content, 200);
        $response
            ->header('Content-Description', 'File Transfer')
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename=' . $quoted)
            ->header('Content-Transfer-Encoding', 'binary')
            ->header('Connection', 'Keep-Alive')
            ->header('Expires', '0')
            ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->header('Pragma', 'public')
            ->header('Content-Length', strlen($content));

        return $response;

    }

    /**
     * @param ExportJob $job
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(ExportJob $job)
    {
        return Response::json(['status' => trans('firefly.' . $job->status)]);
    }

    /**
     * @param AccountRepositoryInterface   $repository
     * @param ExportJobRepositoryInterface $jobs
     *
     * @return View
     */
    public function index(AccountRepositoryInterface $repository, ExportJobRepositoryInterface $jobs)
    {
        // create new export job.
        $job = $jobs->create();
        // delete old ones.
        $jobs->cleanup();

        // does the user have shared accounts?
        $accounts      = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $accountList   = ExpandedForm::makeSelectList($accounts);
        $checked       = array_keys($accountList);
        $formats       = array_keys(config('firefly.export_formats'));
        $defaultFormat = Preferences::get('export_format', config('firefly.default_export_format'))->data;
        $first         = session('first')->format('Y-m-d');
        $today         = Carbon::create()->format('Y-m-d');

        return view('export.index', compact('job', 'checked', 'accountList', 'formats', 'defaultFormat', 'first', 'today'));

    }

    /**
     * @param ExportFormRequest            $request
     * @param AccountRepositoryInterface   $repository
     * @param ExportJobRepositoryInterface $jobs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postIndex(ExportFormRequest $request, AccountRepositoryInterface $repository, ExportJobRepositoryInterface $jobs)
    {
        $job      = $jobs->findByKey($request->get('job'));
        $settings = [
            'accounts'           => $repository->getAccountsById($request->get('accounts')),
            'startDate'          => new Carbon($request->get('export_start_range')),
            'endDate'            => new Carbon($request->get('export_end_range')),
            'exportFormat'       => $request->get('exportFormat'),
            'includeAttachments' => intval($request->get('include_attachments')) === 1,
            'includeOldUploads'  => intval($request->get('include_old_uploads')) === 1,
            'job'                => $job,
        ];

        $jobs->changeStatus($job, 'export_status_make_exporter');

        /** @var ProcessorInterface $processor */
        $processor = app(ProcessorInterface::class);
        $processor->setSettings($settings);

        /*
         * Collect journals:
         */
        $jobs->changeStatus($job, 'export_status_collecting_journals');
        $processor->collectJournals();
        $jobs->changeStatus($job, 'export_status_collected_journals');
        /*
         * Transform to exportable entries:
         */
        $jobs->changeStatus($job, 'export_status_converting_to_export_format');
        $processor->convertJournals();
        $jobs->changeStatus($job, 'export_status_converted_to_export_format');
        /*
         * Transform to (temporary) file:
         */
        $jobs->changeStatus($job, 'export_status_creating_journal_file');
        $processor->exportJournals();
        $jobs->changeStatus($job, 'export_status_created_journal_file');
        /*
         *  Collect attachments, if applicable.
         */
        if ($settings['includeAttachments']) {
            $jobs->changeStatus($job, 'export_status_collecting_attachments');
            $processor->collectAttachments();
            $jobs->changeStatus($job, 'export_status_collected_attachments');
        }

        /*
         * Collect old uploads
         */
        if ($settings['includeOldUploads']) {
            $jobs->changeStatus($job, 'export_status_collecting_old_uploads');
            $processor->collectOldUploads();
            $jobs->changeStatus($job, 'export_status_collected_old_uploads');
        }

        /*
         * Create ZIP file:
         */
        $jobs->changeStatus($job, 'export_status_creating_zip_file');
        $processor->createZipFile();
        $jobs->changeStatus($job, 'export_status_created_zip_file');
        $jobs->changeStatus($job, 'export_status_finished');

        return Response::json('ok');
    }
}
