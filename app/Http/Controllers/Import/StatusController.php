<?php
/**
 * StatusController.php
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

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Response;

/**
 * Class StatusController
 */
class StatusController extends Controller
{
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

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class);
    }

    /**
     * @param ImportJob $job
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function index(ImportJob $job)
    {
        $statuses = ['configured', 'running', 'finished', 'error'];
        if (!in_array($job->status, $statuses)) {
            return redirect(route('import.configure', [$job->key]));
        }
        $subTitle     = trans('import.status_sub_title');
        $subTitleIcon = 'fa-star';

        return view('import.status', compact('job', 'subTitle', 'subTitleIcon'));
    }

    /**
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
            'statusText'      => trans('import.status_job_' . $job->status),
            'status'          => $job->status,
            'finishedText'    => '',
        ];

        if (0 !== $job->extended_status['steps']) {
            $result['percentage']      = round(($job->extended_status['done'] / $job->extended_status['steps']) * 100, 0);
            $result['show_percentage'] = true;
        }
        if ('finished' === $job->status) {
            $result['finished'] = true;
            $tagId              = intval($job->extended_status['tag']);
            if ($tagId !== 0) {
                /** @var TagRepositoryInterface $repository */
                $repository             = app(TagRepositoryInterface::class);
                $tag                    = $repository->find($tagId);
                $count                  = $tag->transactionJournals()->count();
                $result['finishedText'] = trans(
                    'import.status_finished_job', ['count' => $count, 'link' => route('tags.show', [$tag->id, 'all']), 'tag' => $tag->tag]
                );
            }

            if ($tagId === 0) {
                $result['finishedText'] = trans('import.status_finished_no_tag'); // @codeCoverageIgnore
            }
        }

        if ('running' === $job->status) {
            $result['started'] = true;
            $result['running'] = true;
        }
        $result['percentage'] = $result['percentage'] > 100 ? 100 : $result['percentage'];

        return Response::json($result);
    }
}
