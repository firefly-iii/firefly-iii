<?php
/*
 * UpdateController.php
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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Transformers\AvailableBudgetTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class UpdateController
 */
class UpdateController extends Controller
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
     * Update the specified resource in storage.
     *
     * @param Request         $request
     * @param AvailableBudget $availableBudget
     *
     * @return JsonResponse
     */
    public function update(Request $request, AvailableBudget $availableBudget): JsonResponse
    {
        $data = $request->getAll();

        // find and validate currency ID
        if (array_key_exists('currency_id', $data) || array_key_exists('currency_code', $data)) {
            $factory           = app(TransactionCurrencyFactory::class);
            $currency          = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null) ?? app('amount')->getDefaultCurrency();
            $currency->enabled = true;
            $currency->save();
            unset($data['currency_code']);
            $data['currency_id'] = $currency->id;
        }

        $this->abRepository->updateAvailableBudget($availableBudget, $data);
        $manager = $this->getManager();

        /** @var AvailableBudgetTransformer $transformer */
        $transformer = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($availableBudget, $transformer, 'available_budgets');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }
}