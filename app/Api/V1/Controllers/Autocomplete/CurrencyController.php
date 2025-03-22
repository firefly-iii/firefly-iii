<?php

/**
 * CurrencyController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Autocomplete;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteRequest;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class CurrencyController
 */
class CurrencyController extends Controller
{
    private CurrencyRepositoryInterface $repository;

    /**
     * CurrencyController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(CurrencyRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Documentation for this endpoint is at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getCurrenciesAC
     */
    public function currencies(AutocompleteRequest $request): JsonResponse
    {
        $data       = $request->getData();
        $collection = $this->repository->searchCurrency($data['query'], $this->parameters->get('limit'));
        $result     = [];

        /** @var TransactionCurrency $currency */
        foreach ($collection as $currency) {
            $result[] = [
                'id'             => (string) $currency->id,
                'name'           => $currency->name,
                'code'           => $currency->code,
                'symbol'         => $currency->symbol,
                'decimal_places' => $currency->decimal_places,
            ];
        }

        return response()->api($result);
    }

    /**
     * Documentation for this endpoint is at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getCurrenciesCodeAC
     *
     * @deprecated
     */
    public function currenciesWithCode(AutocompleteRequest $request): JsonResponse
    {
        $data       = $request->getData();
        $collection = $this->repository->searchCurrency($data['query'], $this->parameters->get('limit'));
        $result     = [];

        /** @var TransactionCurrency $currency */
        foreach ($collection as $currency) {
            $result[] = [
                'id'             => (string) $currency->id,
                'name'           => sprintf('%s (%s)', $currency->name, $currency->code),
                'code'           => $currency->code,
                'symbol'         => $currency->symbol,
                'decimal_places' => $currency->decimal_places,
            ];
        }

        return response()->api($result);
    }
}
