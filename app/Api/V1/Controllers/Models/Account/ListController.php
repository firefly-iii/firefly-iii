<?php

/**
 * AccountController.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Models\Account;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\PaginationRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Support\JsonApi\Enrichments\PiggyBankEnrichment;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\Transformers\PiggyBankTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class ListController
 */
class ListController extends Controller
{
    use TransactionFilter;

    public const string RESOURCE_KEY = 'accounts';

    private AccountRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    public function attachments(Account $account, PaginationRequest $request): JsonResponse
    {
        $manager     = $this->getManager();
        [
            'limit'  => $limit,
            'offset' => $offset,
            'page'   => $page,
        ]            = $request->attributes->all();
        $collection  = $this->repository->getAttachments($account);

        $count       = $collection->count();
        $attachments = $collection->slice($offset, $limit);

        // make paginator:
        $paginator   = new LengthAwarePaginator($attachments, $count, $limit, $page);
        $paginator->setPath(route('api.v1.accounts.attachments', [$account->id]).$this->buildParams());

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);

        $resource    = new FractalCollection($attachments, $transformer, 'attachments');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    public function piggyBanks(Account $account): JsonResponse
    {
        // create some objects:
        $manager     = $this->getManager();

        // types to get, page size:
        $pageSize    = $this->parameters->get('limit');

        // get list of piggy banks. Count it and split it.
        $collection  = $this->repository->getPiggyBanks($account);
        $count       = $collection->count();
        $piggyBanks  = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // enrich
        /** @var User $admin */
        $admin       = auth()->user();
        $enrichment  = new PiggyBankEnrichment();
        $enrichment->setUser($admin);
        $piggyBanks  = $enrichment->enrich($piggyBanks);

        // make paginator:
        $paginator   = new LengthAwarePaginator($piggyBanks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.accounts.piggy-banks', [$account->id]).$this->buildParams());

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($piggyBanks, $transformer, 'piggy-banks');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Show all transaction groups related to the account.
     */
    public function transactions(Request $request, Account $account): JsonResponse
    {
        $pageSize     = $this->parameters->get('limit');
        $type         = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);
        $types        = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager      = $this->getManager();

        /** @var User $admin */
        $admin        = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector->setUser($admin)->setAccounts(new Collection()->push($account))
            ->withAPIInformation()->setLimit($pageSize)->setPage($this->parameters->get('page'))->setTypes($types)
        ;

        if (null !== $this->parameters->get('start')) {
            $collector->setStart($this->parameters->get('start'));
        }
        if (null !== $this->parameters->get('end')) {
            $collector->setEnd($this->parameters->get('end'));
        }

        $paginator    = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.accounts.transactions', [$account->id]).$this->buildParams());

        // enrich
        $enrichment   = new TransactionGroupEnrichment();
        $enrichment->setUser($admin);
        $transactions = $enrichment->enrich($paginator->getCollection());

        /** @var TransactionGroupTransformer $transformer */
        $transformer  = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
