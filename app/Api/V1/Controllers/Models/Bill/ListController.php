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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var BillRepositoryInterface repository */
                $this->repository = app(BillRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param Bill $bill
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function attachments(Bill $bill): JsonResponse
    {
        $manager    = $this->getManager();
        $pageSize   = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $collection = $this->repository->getAttachments($bill);

        $count       = $collection->count();
        $attachments = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($attachments, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.bills.attachments', [$bill->id]) . $this->buildParams());

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($attachments, $transformer, 'attachments');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * List all of them.
     *
     * @param Bill $bill
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function rules(Bill $bill): JsonResponse
    {
        $manager = $this->getManager();

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->repository->getRulesForBill($bill);
        $count      = $collection->count();
        $rules      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($rules, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.bills.rules', [$bill->id]) . $this->buildParams());

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource = new FractalCollection($rules, $transformer, 'rules');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }

    /**
     * Show all transactions.
     *
     * @param Request $request
     *
     * @param Bill    $bill
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function transactions(Request $request, Bill $bill): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $type     = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager = $this->getManager();

        /** @var User $admin */
        $admin = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // include source + destination account name and type.
            ->setBill($bill)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($pageSize)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes($types);

        // do parameter stuff on new group collector.
        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }

        // get paginator.
        $paginator = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.bills.transactions', [$bill->id]) . $this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

}
