<?php

/**
 * TransactionController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Search;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Search\CountRequest;
use FireflyIII\Api\V1\Requests\Search\TransactionSearchRequest;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\Support\Search\SearchInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;

/**
 * Class TransactionController
 */
final class TransactionController extends Controller
{
    private JournalRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            /** @var User $admin */
            $admin            = auth()->user();

            $this->repository = app(JournalRepositoryInterface::class);
            $this->repository->setUser($admin);

            return $next($request);
        });
    }

    public function count(CountRequest $request, SearchInterface $searcher): JsonResponse
    {
        $count          = 0;
        $includeDeleted = $request->attributes->get('include_deleted', false);
        $externalId     = (string) $request->attributes->get('external_identifier');
        $internalRef    = (string) $request->attributes->get('internal_reference');
        $notes          = (string) $request->attributes->get('notes');
        $description    = (string) $request->attributes->get('description');
        Log::debug(sprintf('Include deleted? %s', var_export($includeDeleted, true)));
        if ('' !== $externalId) {
            $count += $this->repository->countByMeta('external_identifier', $externalId, $includeDeleted);
            Log::debug(sprintf('Search for transactions with external_identifier "%s", count is now %d', $externalId, $count));
        }
        if ('' !== $internalRef) {
            $count += $this->repository->countByMeta('internal_reference', $internalRef, $includeDeleted);
            Log::debug(sprintf('Search for transactions with internal_reference "%s", count is now %d', $internalRef, $count));
        }
        if ('' !== $notes) {
            $count += $this->repository->countByNotes($notes, $includeDeleted);
            Log::debug(sprintf('Search for transactions with notes LIKE "%s", count is now %d', $notes, $count));
        }
        if ('' !== $description) {
            $count += $this->repository->countByDescription($description, $includeDeleted);
            Log::debug(sprintf('Search for transactions with description "%s", count is now %d', $description, $count));
        }

        return response()->json(['count' => $count]);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/search/searchTransactions
     */
    public function search(TransactionSearchRequest $request, SearchInterface $searcher): JsonResponse
    {
        $manager      = $this->getManager();
        $fullQuery    = (string) $request->attributes->get('query');
        $page         = $request->attributes->get('page');
        $pageSize     = $request->attributes->get('limit');
        $searcher->parseQuery($fullQuery);
        $searcher->setPage($page);
        $searcher->setLimit($pageSize);
        $groups       = $searcher->searchTransactions();
        $parameters   = ['search' => $fullQuery];
        $url          = route('api.v1.search.transactions').'?'.http_build_query($parameters);
        $groups->setPath($url);

        // enrich
        $enrichment   = new TransactionGroupEnrichment();
        $enrichment->setUser(auth()->user());
        $transactions = $enrichment->enrich($groups->getCollection());

        /** @var TransactionGroupTransformer $transformer */
        $transformer  = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new Collection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($groups));

        $array        = $manager->createData($resource)->toArray();

        return response()->json($array)->header('Content-Type', self::CONTENT_TYPE);
    }
}
