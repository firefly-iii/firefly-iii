<?php

/*
 * DestroyController.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\CurrencyExchangeRate;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\CurrencyExchangeRate\DestroyRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\ExchangeRate\ExchangeRateRepositoryInterface;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DestroyController extends Controller
{
    use ValidatesUserGroupTrait;

    public const string RESOURCE_KEY = 'exchange-rates';
    protected array $acceptedRoles   = [UserRoleEnum::OWNER];
    private ExchangeRateRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(ExchangeRateRepositoryInterface::class);
                $this->repository->setUserGroup($this->validateUserGroup($request));

                return $next($request);
            }
        );
    }

    public function destroy(DestroyRequest $request, TransactionCurrency $from, TransactionCurrency $to): JsonResponse
    {
        $date = $request->getDate();
        if (!$date instanceof Carbon) {
            throw new ValidationException('Date is required');
        }
        $rate = $this->repository->getSpecificRateOnDate($from, $to, $date);
        if (!$rate instanceof CurrencyExchangeRate) {
            throw new NotFoundHttpException();
        }
        $this->repository->deleteRate($rate);

        return response()->json([], 204);
    }

    public function destroySingle(CurrencyExchangeRate $exchangeRate): JsonResponse
    {
        $this->repository->deleteRate($exchangeRate);

        return response()->json([], 204);
    }
}
