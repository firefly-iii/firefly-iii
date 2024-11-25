<?php

/*
 * ListController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\Recurrence;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class ListController
 */
class ListController extends Controller
{
    use TransactionFilter;

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

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/recurrences/listTransactionByRecurrence
     *
     * Show transactions for this recurrence.
     *
     * @throws FireflyException
     */
    public function transactions(Request $request, Recurrence $recurrence): JsonResponse
    {
        $pageSize     = $this->parameters->get('limit');
        $type         = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types        = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager      = $this->getManager();
        // whatever is returned by the query, it must be part of these journals:
        $journalIds   = $this->repository->getJournalIds($recurrence);

        /** @var User $admin */
        $admin        = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on journal IDs.
            ->setJournalIds($journalIds)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($pageSize)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes($types)
        ;

        if (null !== $this->parameters->get('start')) {
            $collector->setStart($this->parameters->get('start'));
        }
        if (null !== $this->parameters->get('end')) {
            $collector->setEnd($this->parameters->get('end'));
        }

        $paginator    = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.transactions.index').$this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer  = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
