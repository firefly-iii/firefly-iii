<?php

/*
 * AccountController.php
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

namespace FireflyIII\Api\V1\Controllers\Search;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\JsonApi\Enrichments\AccountEnrichment;
use FireflyIII\Support\Search\AccountSearch;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use AccountFilter;

    private array $validFields;

    public function __construct()
    {
        parent::__construct();
        $this->validFields = [
            AccountSearch::SEARCH_ALL,
            AccountSearch::SEARCH_ID,
            AccountSearch::SEARCH_NAME,
            AccountSearch::SEARCH_IBAN,
            AccountSearch::SEARCH_NUMBER,
        ];
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/search/searchAccounts
     */
    public function search(Request $request): JsonResponse|Response
    {
        $manager     = $this->getManager();
        $query       = trim((string) $request->get('query'));
        $field       = trim((string) $request->get('field'));
        $type        = $request->get('type') ?? 'all';
        if ('' === $query || !in_array($field, $this->validFields, true)) {
            return response(null, 422);
        }
        app('log')->debug(sprintf('Now in account search("%s", "%s")', $field, $query));
        $types       = $this->mapAccountTypes($type);

        /** @var AccountSearch $search */
        $search      = app(AccountSearch::class);
        $search->setUser(auth()->user());
        $search->setTypes($types);
        $search->setField($field);
        $search->setQuery($query);

        $accounts    = $search->search();

        // enrich
        /** @var User $admin */
        $admin       = auth()->user();
        $enrichment  = new AccountEnrichment();
        $enrichment->setUser($admin);
        $enrichment->setNative($this->nativeCurrency);
        $accounts    = $enrichment->enrich($accounts);

        /** @var AccountTransformer $transformer */
        $transformer = app(AccountTransformer::class);
        $transformer->setParameters($this->parameters);
        $count       = $accounts->count();
        $perPage     = 0 === $count ? 1 : $count;
        $paginator   = new LengthAwarePaginator($accounts, $count, $perPage, 1);

        $resource    = new FractalCollection($accounts, $transformer, 'accounts');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
