<?php

/*
 * DestroyController.php
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

use Illuminate\Support\Facades\Validator;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private CurrencyRepositoryInterface $repository;
    private UserRepositoryInterface     $userRepository;

    /**
     * CurrencyRepository constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/currencies/deleteCurrency
     *
     * Remove the specified resource from storage.
     *
     * @param TransactionCurrency $currency
     * @return JsonResponse
     * @throws FireflyException
     * @throws ValidationException
     */
    public function destroy(TransactionCurrency $currency): JsonResponse
    {
        /** @var User $admin */
        $admin = auth()->user();
        $rules = ['currency_code' => 'required'];

        if (!$this->userRepository->hasRole($admin, 'owner')) {
            // access denied:
            $messages = ['currency_code' => '200005: You need the "owner" role to do this.'];
            Validator::make([], $rules, $messages)->validate();
        }
        if ($this->repository->currencyInUse($currency)) {
            $messages = ['currency_code' => '200006: Currency in use.'];
            Validator::make([], $rules, $messages)->validate();
        }
        if ($this->repository->isFallbackCurrency($currency)) {
            $messages = ['currency_code' => '200026: Currency is fallback.'];
            Validator::make([], $rules, $messages)->validate();
        }

        $this->repository->destroy($currency);
        app('preferences')->mark();

        return response()->json([], 204);
    }
}
