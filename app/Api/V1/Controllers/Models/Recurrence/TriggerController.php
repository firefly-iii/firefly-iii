<?php

declare(strict_types=1);
/*
 * TriggerController.php
 * Copyright (c) 2025 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Api\V1\Controllers\Models\Recurrence;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Generic\SingleDateRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Jobs\CreateRecurringTransactions;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

class TriggerController extends Controller
{
    private RecurringRepositoryInterface $repository;

    /**
     * RecurrenceController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(RecurringRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    public function trigger(SingleDateRequest $request, Recurrence $recurrence): JsonResponse
    {
        // find recurrence occurrence for this date and trigger it.
        // grab the date from the last time the recurrence fired:
        $backupDate                 = $recurrence->latest_date;
        $date                       = $request->getDate();

        // fire the recurring cron job on the given date, then post-date the created transaction.
        Log::info(sprintf('Trigger: will now fire recurring cron job task for date "%s".', $date->format('Y-m-d H:i:s')));

        /** @var CreateRecurringTransactions $job */
        $job                        = app(CreateRecurringTransactions::class);
        $job->setRecurrences(new Collection()->push($recurrence));
        $job->setDate($date);
        $job->setForce(false);
        $job->handle();
        Log::debug('Done with recurrence.');

        $groups                     = $job->getGroups();
        $this->repository->markGroupsAsNow($groups);
        $recurrence->latest_date    = $backupDate;
        $recurrence->latest_date_tz = $backupDate?->format('e');
        $recurrence->save();
        Preferences::mark();

        // enrich groups and return them:

        if (0 === $groups->count()) {
            $paginator = new LengthAwarePaginator(new Collection(), 0, 1);
        }
        if ($groups->count() > 0) {
            /** @var User $admin */
            $admin     = auth()->user();

            // use new group collector:
            /** @var GroupCollectorInterface $collector */
            $collector = app(GroupCollectorInterface::class);
            $collector
                ->setUser($admin)
                ->setIds($groups->pluck('id')->toArray())
                ->withAPIInformation()
            ;
            $paginator = $collector->getPaginatedGroups();
        }

        $manager                    = $this->getManager();
        $paginator->setPath(route('api.v1.recurrences.trigger', [$recurrence->id]).$this->buildParams());

        // enrich
        $admin                      = auth()->user();
        $enrichment                 = new TransactionGroupEnrichment();
        $enrichment->setUser($admin);
        $transactions               = $enrichment->enrich($paginator->getCollection());

        /** @var TransactionGroupTransformer $transformer */
        $transformer                = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource                   = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
