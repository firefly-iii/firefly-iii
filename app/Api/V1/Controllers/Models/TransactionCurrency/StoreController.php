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

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\TransactionCurrency;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\TransactionCurrency\StoreRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\CurrencyTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    use AccountFilter, TransactionFilter;

    private CurrencyRepositoryInterface $repository;
    private UserRepositoryInterface     $userRepository;

    /**
     * CurrencyRepository constructor.
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

                /** @var CurrencyRepositoryInterface repository */
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Store new currency.
     *
     * @param StoreRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $currency = $this->repository->store($request->getAll());
        if (true === $request->boolean('default')) {
            app('preferences')->set('currencyPreference', $currency->code);
            app('preferences')->mark();
        }
        $manager         = $this->getManager();
        $defaultCurrency = app('amount')->getDefaultCurrencyByUser(auth()->user());
        $this->parameters->set('defaultCurrency', $defaultCurrency);

        /** @var CurrencyTransformer $transformer */
        $transformer = app(CurrencyTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($currency, $transformer, 'currencies');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
