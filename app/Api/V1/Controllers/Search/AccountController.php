<?php
/**
 * AccountController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers\Search;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Search\AccountSearch;
use FireflyIII\Transformers\AccountTransformer;
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

    /** @var array */
    private $validFields;

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
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function search(Request $request)
    {
        $manager = $this->getManager();
        $query   = $request->get('query');
        $field   = $request->get('field');
        $type    = $request->get('type') ?? 'all';
        if ('' === $query || !in_array($field, $this->validFields, true)) {
            return response(null, 422);
        }
        $types = $this->mapAccountTypes($type);
        /** @var AccountSearch $search */
        $search = app(AccountSearch::class);
        $search->setUser(auth()->user());
        $search->setTypes($types);
        $search->setField($field);
        $search->setQuery($query);

        $accounts = $search->search();

        /** @var AccountTransformer $transformer */
        $transformer = app(AccountTransformer::class);
        $transformer->setParameters($this->parameters);
        $count     = $accounts->count();
        $perPage   = 0 === $count ? 1 : $count;
        $paginator = new LengthAwarePaginator($accounts, $count, $perPage, 1);

        $resource = new FractalCollection($accounts, $transformer, 'accounts');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

}