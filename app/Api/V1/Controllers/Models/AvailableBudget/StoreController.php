<?php
/*
 * StoreController.php
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

namespace FireflyIII\Api\V1\Controllers\Models\AvailableBudget;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\AvailableBudget\Request;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Transformers\AvailableBudgetTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    private AvailableBudgetRepositoryInterface $abRepository;

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
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->getAll();
        $data['start']->startOfDay();
        $data['end']->endOfDay();

        // currency is not mandatory:
        if (array_key_exists('currency_id', $data) || array_key_exists('currency_code', $data)) {
            $factory             = app(TransactionCurrencyFactory::class);
            $currency            = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);
            $data['currency_id'] = $currency->id;
            unset($data['currency_code']);
        }
        if (!array_key_exists('currency_id', $data)) {
            $currency            = app('amount')->getDefaultCurrencyByUser(auth()->user());
            $data['currency_id'] = $currency->id;
        }

        $availableBudget = $this->abRepository->store($data);
        $manager         = $this->getManager();

        /** @var AvailableBudgetTransformer $transformer */
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($availableBudget, $transformer, 'available_budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}