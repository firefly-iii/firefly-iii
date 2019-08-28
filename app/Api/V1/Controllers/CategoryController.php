<?php
/**
 * CategoryController.php
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

use FireflyIII\Api\V1\Requests\CategoryRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\CategoryTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;

/**
 * Class CategoryController.
 *
 */
class CategoryController extends Controller
{
    use TransactionFilter;
    /** @var CategoryRepositoryInterface The category repository */
    private $repository;

    /**
     * CategoryController constructor.
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

                /** @var CategoryRepositoryInterface repository */
                $this->repository = app(CategoryRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Category $category
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function delete(Category $category): JsonResponse
    {
        $this->repository->destroy($category);

        return response()->json([], 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(Request $request): JsonResponse
    {
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->repository->getCategories();
        $count      = $collection->count();
        $categories = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($categories, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.categories.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var CategoryTransformer $transformer */
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters($this->parameters);


        $resource = new FractalCollection($categories, $transformer, 'categories');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Show the category.
     *
     * @param Request $request
     * @param Category $category
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Request $request, Category $category): JsonResponse
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var CategoryTransformer $transformer */
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($category, $transformer, 'categories');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new category.
     *
     * @param CategoryRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->repository->store($request->getAll());
        if (null !== $category) {
            $manager = new Manager();
            $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
            $manager->setSerializer(new JsonApiSerializer($baseUrl));

            /** @var CategoryTransformer $transformer */
            $transformer = app(CategoryTransformer::class);
            $transformer->setParameters($this->parameters);

            $resource = new Item($category, $transformer, 'categories');

            return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
        }
        throw new FireflyException('Could not store new category.'); // @codeCoverageIgnore
    }

    /**
     * Show all transactions.
     *
     * @param Request $request
     *
     * @param Category $category
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function transactions(Request $request, Category $category): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $type     = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var User $admin */
        $admin = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on category.
            ->setCategory($category)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($pageSize)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes($types);

        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }

        $paginator = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.categories.transactions', [$category->id]) . $this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update the category.
     *
     * @param CategoryRequest $request
     * @param Category $category
     *
     * @return JsonResponse
     */
    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $data     = $request->getAll();
        $category = $this->repository->update($category, $data);
        $manager  = new Manager();
        $baseUrl  = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var CategoryTransformer $transformer */
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($category, $transformer, 'categories');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

}
