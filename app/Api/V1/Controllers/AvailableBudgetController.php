<?php
/**
 * AvailableBudgetController.php
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

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\AvailableBudgetRequest;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Transformers\AvailableBudgetTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class AvailableBudgetController.
 *
 */
class AvailableBudgetController extends Controller
{
    /** @var AvailableBudgetRepositoryInterface */
    private $abRepository;

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
                $user               = auth()->user();
                $this->abRepository = app(AvailableBudgetRepositoryInterface::class);
                $this->abRepository->setUser($user);

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
        $this->abRepository->destroyAvailableBudget($availableBudget);

        return response()->json([], 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(): JsonResponse
    {
        $manager = $this->getManager();

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        $start = $this->parameters->get('start');
        $end   = $this->parameters->get('end');

        // get list of available budgets. Count it and split it.
        $collection       = $this->abRepository->getAvailableBudgetsByDate($start, $end);
        $count            = $collection->count();
        $availableBudgets = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($availableBudgets, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.available_budgets.index') . $this->buildParams());

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
     * @param AvailableBudget $availableBudget
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(AvailableBudget $availableBudget): JsonResponse
    {
        $manager = $this->getManager();

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
        $data['currency'] = $currency;
        $availableBudget  = $this->abRepository->store($data);
        $manager          = $this->getManager();

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
     * @param AvailableBudget        $availableBudget
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


        $this->abRepository->updateAvailableBudget($availableBudget, $data);
        $manager = $this->getManager();

        /** @var AvailableBudgetTransformer $transformer */
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($availableBudget, $transformer, 'available_budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
