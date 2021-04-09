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

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
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
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param TransactionCurrency $currency
     *
     * @return JsonResponse
     * @throws FireflyException
     * @codeCoverageIgnore
     */
    public function destroy(TransactionCurrency $currency): JsonResponse
    {
        /** @var User $admin */
        $admin = auth()->user();

        if (!$this->userRepository->hasRole($admin, 'owner')) {
            // access denied:
            throw new FireflyException('200005: You need the "owner" role to do this.'); 
        }
        if ($this->repository->currencyInUse($currency)) {
            throw new FireflyException('200006: Currency in use.'); 
        }
        if ($this->repository->isFallbackCurrency($currency)) {
            throw new FireflyException('200026: Currency is fallback.'); 
        }

        $this->repository->destroy($currency);

        return response()->json([], 204);
    }
}
