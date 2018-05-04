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
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Log;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * Class JobStatusController
 */
class JobStatusController extends Controller
{
    /** @var ImportJobRepositoryInterface */
    private $repository;

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
        $this->middleware(IsDemoUser::class);
    }

    /**
     * @param ImportJob $importJob
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(ImportJob $importJob)
    {
        // jump away depending on job status:
        if ($importJob->status === 'has_prereq') {
            // TODO back to configuration.
        }

        if ($importJob->status === 'errored') {
            // TODO to error screen
        }

        if ($importJob->status === 'finished') {
            // TODO to finished screen.
        }

        $subTitleIcon = 'fa-gear';
        $subTitle     = trans('import.job_status_breadcrumb', ['key' => $importJob->key]);

        return view('import.status', compact('importJob', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @param ImportJob $importJob
     *
     * @return JsonResponse
     */
    public function json(ImportJob $importJob): JsonResponse
    {
        $extendedStatus = $importJob->extended_status;
        $count          = \count($importJob->transactions);
        $json           = [
            'status'     => $importJob->status,
            'errors'     => $importJob->errors,
            'count'      => $count,
            'tag_id'     => $importJob->tag_id,
            'tag_name'   => null === $importJob->tag_id ? null : $importJob->tag->tag,
            'journals'   => $extendedStatus['count'] ?? 0,
            'report_txt' => trans('import.unknown_import_result'),
        ];
        // if count is zero:
        if ($count === 0) {
            $json['report_txt'] = trans('import.result_no_transactions');
        }
        if ($count === 1 && null !== $importJob->tag_id) {
            $json['report_txt'] = trans('import.result_one_transaction', ['route' => route('tags.show', [$importJob->tag_id]), 'tag' => $importJob->tag->tag ]);
        }
        if ($count > 1 && null !== $importJob->tag_id) {
            $json['report_txt'] = trans('import.result_many_transactions', ['count' => $count,'route' => route('tags.show', [$importJob->tag_id]), 'tag' => $importJob->tag->tag ]);
        }

        return response()->json($json);
    }

    /**
     * @param ImportJob $job
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function start(ImportJob $importJob): JsonResponse
    {
        // catch impossible status:
        $allowed = ['ready_to_run', 'need_job_config'];
        if (null !== $importJob && !in_array($importJob->status, $allowed)) {
            Log::error('Job is not ready.');

            return response()->json(['status' => 'NOK', 'message' => 'JobStatusController::start expects state "ready_to_run".']);
        }

        $importProvider = $importJob->provider;
        $key            = sprintf('import.routine.%s', $importProvider);
        $className      = config($key);
        if (null === $className || !class_exists($className)) {
            return response()->json(['status' => 'NOK', 'message' => sprintf('Cannot find import routine class for job of type "%s".', $importProvider)]);
        }


        // if the job is set to "provider_finished", we should be able to store transactions
        // generated by the provider.
        // otherwise, just continue.
        if ($importJob->status === 'provider_finished') {
            try {
                $this->importFromJob($importJob);
            } catch (FireflyException $e) {
                $message = 'The import storage routine crashed: ' . $e->getMessage();
                Log::error($message);
                Log::error($e->getTraceAsString());

                // set job errored out:
                $this->repository->setStatus($importJob, 'error');

                return response()->json(['status' => 'NOK', 'message' => $message]);
            }
        }


        // set job to be running:
        $this->repository->setStatus($importJob, 'running');

        /** @var RoutineInterface $routine */
        $routine = app($className);
        $routine->setJob($importJob);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $message = 'The import routine crashed: ' . $e->getMessage();
            Log::error($message);
            Log::error($e->getTraceAsString());

            // set job errored out:
            $this->repository->setStatus($importJob, 'error');

            return response()->json(['status' => 'NOK', 'message' => $message]);
        }

        // expect nothing from routine, just return OK to user.
        return response()->json(['status' => 'OK', 'message' => 'stage_finished']);
    }

    /**
     * @param ImportJob $job
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(ImportJob $importJob): JsonResponse
    {
        // catch impossible status:
        $allowed = ['provider_finished', 'storing_data']; // todo remove storing data.
        if (null !== $importJob && !in_array($importJob->status, $allowed)) {
            Log::error('Job is not ready.');

            return response()->json(['status' => 'NOK', 'message' => 'JobStatusController::start expects state "provider_finished".']);
        }

        // set job to be storing data:
        $this->repository->setStatus($importJob, 'storing_data');

        try {
            $this->importFromJob($importJob);
        } catch (FireflyException $e) {
            $message = 'The import storage routine crashed: ' . $e->getMessage();
            Log::error($message);
            Log::error($e->getTraceAsString());

            // set job errored out:
            $this->repository->setStatus($importJob, 'error');

            return response()->json(['status' => 'NOK', 'message' => $message]);
        }

        // set job to be finished.
        $this->repository->setStatus($importJob, 'finished');

        // expect nothing from routine, just return OK to user.
        return response()->json(['status' => 'OK', 'message' => 'storage_finished']);
    }

    /**
     * @param ImportJob $importJob
     *
     * @throws FireflyException
     */
    private function importFromJob(ImportJob $importJob): void
    {
        try {
            $storage                 = new ImportArrayStorage($importJob);
            $journals                = $storage->store();
            $extendedStatus          = $importJob->extended_status;
            $extendedStatus['count'] = $journals->count();
            $this->repository->setExtendedStatus($importJob, $extendedStatus);
        } catch (FireflyException|Exception|FatalThrowableError $e) {
            throw new FireflyException($e->getMessage());
        }


    }

    //    /**
    //     * @param ImportJob $job
    //     *
    //     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
    //     */
    //    public function index(ImportJob $job)
    //    {
    //        $statuses = ['configured', 'running', 'finished', 'error'];
    //        if (!\in_array($job->status, $statuses)) {
    //            return redirect(route('import.configure', [$job->key]));
    //        }
    //        $subTitle     = trans('import.status_sub_title');
    //        $subTitleIcon = 'fa-star';
    //
    //        return view('import.status', compact('job', 'subTitle', 'subTitleIcon'));
    //    }
    //
    //    /**
    //     * Show status of import job in JSON.
    //     *
    //     * @param ImportJob $job
    //     *
    //     * @return \Illuminate\Http\JsonResponse
    //     */
    //    public function json(ImportJob $job)
    //    {
    //        $result = [
    //            'started'         => false,
    //            'finished'        => false,
    //            'running'         => false,
    //            'errors'          => array_values($job->extended_status['errors']),
    //            'percentage'      => 0,
    //            'show_percentage' => false,
    //            'steps'           => $job->extended_status['steps'],
    //            'done'            => $job->extended_status['done'],
    //            'statusText'      => trans('import.status_job_' . $job->status),
    //            'status'          => $job->status,
    //            'finishedText'    => '',
    //        ];
    //
    //        if (0 !== $job->extended_status['steps']) {
    //            $result['percentage']      = round(($job->extended_status['done'] / $job->extended_status['steps']) * 100, 0);
    //            $result['show_percentage'] = true;
    //        }
    //        if ('finished' === $job->status) {
    //            $result['finished'] = true;
    //            $tagId              = (int)$job->extended_status['tag'];
    //            if ($tagId !== 0) {
    //                /** @var TagRepositoryInterface $repository */
    //                $repository             = app(TagRepositoryInterface::class);
    //                $tag                    = $repository->find($tagId);
    //                $count                  = $tag->transactionJournals()->count();
    //                $result['finishedText'] = trans(
    //                    'import.status_finished_job', ['count' => $count, 'link' => route('tags.show', [$tag->id, 'all']), 'tag' => $tag->tag]
    //                );
    //            }
    //
    //            if ($tagId === 0) {
    //                $result['finishedText'] = trans('import.status_finished_no_tag'); // @codeCoverageIgnore
    //            }
    //        }
    //
    //        if ('running' === $job->status) {
    //            $result['started'] = true;
    //            $result['running'] = true;
    //        }
    //        $result['percentage'] = $result['percentage'] > 100 ? 100 : $result['percentage'];
    //        Log::debug(sprintf('JOB STATUS: %d/%d', $result['done'], $result['steps']));
    //
    //        return response()->json($result);
    //    }
}
