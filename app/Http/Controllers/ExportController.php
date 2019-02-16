<?php
/**
 * ExportController.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Requests\ExportFormRequest;
use FireflyIII\Models\ExportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as LaravelResponse;

/**
 * Class ExportController.
 */
class ExportController extends Controller
{
    /**
     * ExportController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-file-archive-o');
                app('view')->share('title', (string)trans('firefly.export_and_backup_data'));

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index']);
    }

    /**
     * Download exported file.
     *
     * @param ExportJobRepositoryInterface $repository
     * @param ExportJob                    $job
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     *
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

        $repository->changeStatus($job, 'export_downloaded');
        /** @var LaravelResponse $response */
        $response = response($content);
        $response
            ->header('Content-Description', 'File Transfer')
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename=' . $quoted)
            ->header('Content-Transfer-Encoding', 'binary')
            ->header('Connection', 'Keep-Alive')
            ->header('Expires', '0')
            ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->header('Pragma', 'public')
            ->header('Content-Length', \strlen($content));

        return $response;
    }

    /**
     * Get current export status.
     *
     * @param ExportJob $job
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(ExportJob $job): JsonResponse
    {
        return response()->json(['status' => (string)trans('firefly.' . $job->status)]);
    }

    /**
     * Index of export routine.
     *
     * @param ExportJobRepositoryInterface $jobs
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(ExportJobRepositoryInterface $jobs)
    {
        // create new export job.
        $job = $jobs->create();

        // does the user have shared accounts?
        $formats       = array_keys(config('firefly.export_formats'));
        $defaultFormat = app('preferences')->get('export_format', config('firefly.default_export_format'))->data;
        $first         = session('first')->format('Y-m-d');
        $today         = Carbon::now()->format('Y-m-d');

        return view('export.index', compact('job', 'formats', 'defaultFormat', 'first', 'today'));
    }

    /**
     * Submit the job.
     *
     * @param ExportFormRequest            $request
     * @param AccountRepositoryInterface   $repository
     * @param ExportJobRepositoryInterface $jobs
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function postIndex(ExportFormRequest $request, AccountRepositoryInterface $repository, ExportJobRepositoryInterface $jobs): JsonResponse
    {
        $job      = $jobs->findByKey($request->get('job'));
        $accounts = $request->get('accounts') ?? [];
        $settings = [
            'accounts'           => $repository->getAccountsById($accounts),
            'startDate'          => new Carbon($request->get('export_start_range')),
            'endDate'            => new Carbon($request->get('export_end_range')),
            'exportFormat'       => $request->get('exportFormat'),
            'includeAttachments' => $request->boolean('include_attachments'),
            'includeOldUploads'  => $request->boolean('include_old_uploads'),
            'job'                => $job,
        ];

        $jobs->changeStatus($job, 'export_status_make_exporter');

        /** @var ProcessorInterface $processor */
        $processor = app(ProcessorInterface::class);
        $processor->setSettings($settings);

        // Collect journals:
        $jobs->changeStatus($job, 'export_status_collecting_journals');
        $processor->collectJournals();
        $jobs->changeStatus($job, 'export_status_collected_journals');

        // Transform to exportable entries:
        $jobs->changeStatus($job, 'export_status_converting_to_export_format');
        $processor->convertJournals();
        $jobs->changeStatus($job, 'export_status_converted_to_export_format');

        // Transform to (temporary) file:
        $jobs->changeStatus($job, 'export_status_creating_journal_file');
        $processor->exportJournals();
        $jobs->changeStatus($job, 'export_status_created_journal_file');
        // Collect attachments, if applicable.
        if ($settings['includeAttachments']) {
            $jobs->changeStatus($job, 'export_status_collecting_attachments');
            $processor->collectAttachments();
            $jobs->changeStatus($job, 'export_status_collected_attachments');
        }

        // Collect old uploads
        if ($settings['includeOldUploads']) {
            $jobs->changeStatus($job, 'export_status_collecting_old_uploads');
            $processor->collectOldUploads();
            $jobs->changeStatus($job, 'export_status_collected_old_uploads');
        }

        // Create ZIP file:
        $jobs->changeStatus($job, 'export_status_creating_zip_file');
        $processor->createZipFile();
        $jobs->changeStatus($job, 'export_status_created_zip_file');
        $jobs->changeStatus($job, 'export_status_finished');

        return response()->json('ok');
    }
}
