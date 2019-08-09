<?php
/**
 * AvailableBudgetController.php
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

use FireflyIII\Api\V1\Requests\AvailableBudgetRequest;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Transformers\AvailableBudgetTransformer;
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
 * Class AvailableBudgetController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AvailableBudgetController extends Controller
{
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * AvailableBudgetController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(BudgetRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param AvailableBudget $availableBudget
     *
     * @codeCoverageIgnore
     *
     * @return JsonResponse
     */
    public function delete(AvailableBudget $availableBudget): JsonResponse
    {
        $this->repository->destroyAvailableBudget($availableBudget);

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

        $start = $this->parameters->get('start');
        $end   = $this->parameters->get('end');

        // get list of available budgets. Count it and split it.
        $collection       = $this->repository->getAvailableBudgetsByDate($start, $end);
        $count            = $collection->count();
        $availableBudgets = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($availableBudgets, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.available_budgets.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var AvailableBudgetTransformer $transformer */
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($availableBudgets, $transformer, 'available_budgets');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param AvailableBudget $availableBudget
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Request $request, AvailableBudget $availableBudget): JsonResponse
    {
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var AvailableBudgetTransformer $transformer */
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($availableBudget, $transformer, 'available_budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AvailableBudgetRequest $request
     *
     * @return JsonResponse
     */
    public function store(AvailableBudgetRequest $request): JsonResponse
    {
        $data = $request->getAll();
        /** @var TransactionCurrencyFactory $factory */
        $factory  = app(TransactionCurrencyFactory::class);
        $currency = $factory->find($data['currency_id'], $data['currency_code']);

        if (null === $currency) {
            $currency = app('amount')->getDefaultCurrency();
        }
        $availableBudget = $this->repository->setAvailableBudget($currency, $data['start'], $data['end'], $data['amount']);
        $manager         = new Manager;
        $baseUrl         = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var AvailableBudgetTransformer $transformer */
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($availableBudget, $transformer, 'available_budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param AvailableBudgetRequest $request
     * @param AvailableBudget $availableBudget
     *
     * @return JsonResponse
     */
    public function update(AvailableBudgetRequest $request, AvailableBudget $availableBudget): JsonResponse
    {
        $data = $request->getAll();

        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        /** @var TransactionCurrency $currency */
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);

        if (null === $currency) {
            // use default currency:
            $currency = app('amount')->getDefaultCurrency();
        }
        $currency->enabled = true;
        $currency->save();
        unset($data['currency_code']);
        $data['currency_id'] = $currency->id;


        $this->repository->updateAvailableBudget($availableBudget, $data);
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var AvailableBudgetTransformer $transformer */
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($availableBudget, $transformer, 'available_budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
