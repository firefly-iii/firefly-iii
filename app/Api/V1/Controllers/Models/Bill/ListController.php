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

namespace FireflyIII\Api\V1\Controllers\Models\Bill;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Generic\PaginationDateRangeRequest;
use FireflyIII\Api\V1\Requests\PaginationRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class ListController
 */
class ListController extends Controller
{
    use TransactionFilter;

    private BillRepositoryInterface $repository;

    /**
     * BillController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(BillRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/bills/listAttachmentByBill
     *
     * Display a listing of the resource.
     */
    public function attachments(PaginationRequest $request, Bill $bill): JsonResponse
    {
        [
            'limit'  => $limit,
            'offset' => $offset,
            'page'   => $page,
        ]            = $request->attributes->all();
        $manager     = $this->getManager();
        $collection  = $this->repository->getAttachments($bill);

        $count       = $collection->count();
        $attachments = $collection->slice($offset, $limit);

        // make paginator:
        $paginator   = new LengthAwarePaginator($attachments, $count, $limit, $page);
        $paginator->setPath(route('api.v1.bills.attachments', [$bill->id]).$this->buildParams());

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);

        $resource    = new FractalCollection($attachments, $transformer, 'attachments');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/bills/listRuleByBill
     *
     * List all of them.
     */
    public function rules(PaginationRequest $request, Bill $bill): JsonResponse
    {
        [
            'limit'  => $limit,
            'offset' => $offset,
            'page'   => $page,
        ]            = $request->attributes->all();

        $manager     = $this->getManager();
        $collection  = $this->repository->getRulesForBill($bill);
        $count       = $collection->count();
        $rules       = $collection->slice($offset, $limit);

        // make paginator:
        $paginator   = new LengthAwarePaginator($rules, $count, $limit, $page);
        $paginator->setPath(route('api.v1.bills.rules', [$bill->id]).$this->buildParams());

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $resource    = new FractalCollection($rules, $transformer, 'rules');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/bills/listTransactionByBill
     *
     * Show all transactions.
     */
    public function transactions(PaginationDateRangeRequest $request, Bill $bill): JsonResponse
    {
        [
            'limit'  => $limit,
            'page'   => $page,
            'types'  => $types,
            'start'  => $start,
            'end'    => $end,
        ]             = $request->attributes->all();

        $manager      = $this->getManager();

        /** @var User $admin */
        $admin        = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // include source + destination account name and type.
            ->setBill($bill)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($limit)
            // set page to retrieve
            ->setPage($page)
            // set types of transactions to return.
            ->setTypes($types)
        ;

        if (null !== $start) {
            $collector->setStart($start);
        }
        if (null !== $end) {
            $collector->setEnd($end);
        }

        // get paginator.
        $paginator    = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.bills.transactions', [$bill->id]).$this->buildParams());

        // enrich
        $enrichment   = new TransactionGroupEnrichment();
        $enrichment->setUser($admin);
        $transactions = $enrichment->enrich($paginator->getCollection());

        /** @var TransactionGroupTransformer $transformer */
        $transformer  = app(TransactionGroupTransformer::class);

        $resource     = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
