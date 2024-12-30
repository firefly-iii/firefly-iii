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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Search\SearchInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;

/**
 * Class TransactionController
 */
class TransactionController extends Controller
{
    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/search/searchTransactions
     *
     * @throws FireflyException
     */
    public function search(Request $request, SearchInterface $searcher): JsonResponse
    {
        $manager      = $this->getManager();
        $fullQuery    = (string) $request->get('query');
        $page         = 0 === (int) $request->get('page') ? 1 : (int) $request->get('page');
        $pageSize     = $this->parameters->get('limit');
        $searcher->parseQuery($fullQuery);
        $searcher->setPage($page);
        $searcher->setLimit($pageSize);
        $groups       = $searcher->searchTransactions();
        $parameters   = ['search' => $fullQuery];
        $url          = route('api.v1.search.transactions').'?'.http_build_query($parameters);
        $groups->setPath($url);
        $transactions = $groups->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer  = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new Collection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($groups));

        $array        = $manager->createData($resource)->toArray();

        return response()->json($array)->header('Content-Type', self::CONTENT_TYPE);
    }
}
