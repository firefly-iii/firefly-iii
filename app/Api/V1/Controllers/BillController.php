<?php
/**
 * BillController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\BillRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use Preferences;

/**
 * Class BillController
 */
class BillController extends Controller
{
    /** @var BillRepositoryInterface */
    private $repository;

    /**
     * BillController constructor.
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var BillRepositoryInterface repository */
                $this->repository = app(BillRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \FireflyIII\Models\Bill $bill
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Bill $bill)
    {
        $this->repository->destroy($bill);

        return response()->json([], 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $pageSize  = intval(Preferences::getForUser(auth()->user(), 'listPageSize', 50)->data);
        $paginator = $this->repository->getPaginator($pageSize);
        /** @var Collection $bills */
        $bills = $paginator->getCollection();

        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new FractalCollection($bills, new BillTransformer($this->parameters), 'bills');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * @param Request $request
     * @param Bill    $bill
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Bill $bill)
    {
        $manager = new Manager();
        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($bill, new BillTransformer($this->parameters), 'bills');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param BillRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(BillRequest $request)
    {
        $bill    = $this->repository->store($request->getAll());
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($bill, new BillTransformer($this->parameters), 'bills');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }


    /**
     * @param BillRequest $request
     * @param Bill        $bill
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(BillRequest $request, Bill $bill)
    {
        $data    = $request->getAll();
        $bill    = $this->repository->update($bill, $data);
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($bill, new BillTransformer($this->parameters), 'bills');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
