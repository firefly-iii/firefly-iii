<?php
/**
 * JobStatusController.php
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

namespace FireflyIII\Http\Controllers\Import;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use Illuminate\Http\JsonResponse;
use Log;

/**
 * Class JobStatusController
 */
class JobStatusController extends Controller
{
    use CreateStuff;
    /** @var ImportJobRepositoryInterface The import job repository */
    private $repository;

    /**
     * JobStatusController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // set time limit to zero to prevent timeouts.
        set_time_limit(0);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-archive');
                app('view')->share('title', (string)trans('firefly.import_index_title'));
                $this->repository = app(ImportJobRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Index for job status.
     *
     * @param ImportJob $importJob
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(ImportJob $importJob)
    {
        $subTitleIcon = 'fa-gear';
        $subTitle     = (string)trans('import.job_status_breadcrumb', ['key' => $importJob->key]);

        return view('import.status', compact('importJob', 'subTitle', 'subTitleIcon'));
    }

    /**
     * JSON overview of job status.
     *
     * @param ImportJob $importJob
     *
     * @return JsonResponse
     */
    public function json(ImportJob $importJob): JsonResponse
    {
        $count = $this->repository->countTransactions($importJob);
        $json  = [
            'status'               => $importJob->status,
            'errors'               => $importJob->errors,
            'count'                => $count,
            'tag_id'               => $importJob->tag_id,
            'tag_name'             => null === $importJob->tag_id ? null : $importJob->tag->tag,
            'report_txt'           => (string)trans('import.unknown_import_result'),
            'download_config'      => false,
            'download_config_text' => '',
        ];

        if ('file' === $importJob->provider) {
            $json['download_config'] = true;
            $json['download_config_text']
                                     = trans('import.should_download_config', ['route' => route('import.job.download', [$importJob->key])]) . ' '
                                       . trans('import.share_config_file');
        }

        // if count is zero:
        if (null !== $importJob->tag_id) {
            $count = $this->repository->countByTag($importJob);
        }
        if (0 === $count) {
            $json['report_txt'] = (string)trans('import.result_no_transactions');
        }
        if (1 === $count && null !== $importJob->tag_id) {
            $json['report_txt'] = trans(
                'import.result_one_transaction', ['route' => route('tags.show', [$importJob->tag_id, 'all']), 'tag' => $importJob->tag->tag]
            );
        }
        if ($count > 1 && null !== $importJob->tag_id) {
            $json['report_txt'] = trans(
                'import.result_many_transactions',
                ['count' => $count, 'route' => route('tags.show', [$importJob->tag_id, 'all']), 'tag' => $importJob->tag->tag]
            );
        }

        return response()->json($json);
    }

    /**
     * Calls to start the job.
     *
     * @param ImportJob $importJob
     *
     * @return JsonResponse
     */
    public function start(ImportJob $importJob): JsonResponse
    {
        Log::info('Now in JobStatusController::start');
        // catch impossible status:
        $allowed = ['ready_to_run', 'need_job_config'];

        if (null !== $importJob && !in_array($importJob->status, $allowed, true)) {
            Log::error(sprintf('Job is not ready. Status should be in array, but is %s', $importJob->status), $allowed);
            $this->repository->setStatus($importJob, 'error');

            return response()->json(
                ['status' => 'NOK', 'message' => sprintf('JobStatusController::start expects status "ready_to_run" instead of "%s".', $importJob->status)]
            );
        }
        $importProvider = $importJob->provider;
        $key            = sprintf('import.routine.%s', $importProvider);
        $className      = config($key);
        if (null === $className || !class_exists($className)) {
            // @codeCoverageIgnoreStart
            $message = sprintf('Cannot find import routine class for job of type "%s".', $importProvider);
            Log::error($message);

            return response()->json(
                ['status' => 'NOK', 'message' => $message]
            );
            // @codeCoverageIgnoreEnd
        }

        /** @var RoutineInterface $routine */
        $routine = app($className);
        $routine->setImportJob($importJob);

        Log::debug(sprintf('Created class of type %s', $className));

        try {
            Log::debug(sprintf('Try to call %s:run()', $className));
            $routine->run();
        } catch (FireflyException|Exception $e) {
            $message = 'The import routine crashed: ' . $e->getMessage();
            Log::error($message);
            Log::error($e->getTraceAsString());

            // set job errored out:
            $this->repository->setStatus($importJob, 'error');

            return response()->json(['status' => 'NOK', 'message' => $message]);
        }

        // expect nothing from routine, just return OK to user.
        Log::info('Now finished with JobStatusController::start');

        return response()->json(['status' => 'OK', 'message' => 'stage_finished']);
    }

    /**
     * Store does three things:
     *
     * - Store the transactions.
     * - Add them to a tag.
     *
     * @param ImportJob $importJob
     *
     * @return JsonResponse
     */
    public function store(ImportJob $importJob): JsonResponse
    {
        Log::info('Now in JobStatusController::store');
        // catch impossible status:
        $allowed = ['provider_finished', 'storing_data'];
        if (null !== $importJob && !in_array($importJob->status, $allowed, true)) {
            Log::error(sprintf('Job is not ready. Status should be in array, but is %s', $importJob->status), $allowed);

            return response()->json(
                ['status' => 'NOK', 'message' => sprintf('JobStatusController::start expects status "provider_finished" instead of "%s".', $importJob->status)]
            );
        }

        // set job to be storing data:
        $this->repository->setStatus($importJob, 'storing_data');

        try {
            $this->storeTransactions($importJob);
        } catch (FireflyException $e) {
            $message = 'The import storage routine crashed: ' . $e->getMessage();
            Log::error($message);
            Log::error($e->getTraceAsString());

            // set job errored out:
            $this->repository->setStatus($importJob, 'error');

            return response()->json(['status' => 'NOK', 'message' => $message]);
        }
        // set storage to be finished:
        $this->repository->setStatus($importJob, 'storage_finished');

        Log::info('Now finished with JobStatusController::start');

        // expect nothing from routine, just return OK to user.
        return response()->json(['status' => 'OK', 'message' => 'storage_finished']);
    }


}
