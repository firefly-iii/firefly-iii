<?php

/*
 * DateController.php
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

namespace FireflyIII\Api\V1\Controllers\Insight\Income;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Insight\GenericRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Api\ApiSupport;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 *
 * Shows income information grouped or limited by date.
 * I.e. all income grouped by account + currency.
 */
class AccountController extends Controller
{
    use ApiSupport;

    private OperationsRepositoryInterface $opsRepository;
    private AccountRepositoryInterface    $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $user                = auth()->user();
                $this->repository    = app(AccountRepositoryInterface::class);
                $this->repository->setUser($user);

                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->opsRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/insight/insightIncomeAsset
     */
    public function asset(GenericRequest $request): JsonResponse
    {
        $start         = $request->getStart();
        $end           = $request->getEnd();
        $assetAccounts = $request->getAssetAccounts();

        $income        = $this->opsRepository->sumIncomeByDestination($start, $end, $assetAccounts);
        $result        = [];

        /** @var array $entry */
        foreach ($income as $entry) {
            $result[] = [
                'id'               => (string) $entry['id'],
                'name'             => $entry['name'],
                'difference'       => $entry['sum'],
                'difference_float' => (float) $entry['sum'], // float but on purpose.
                'currency_id'      => (string) $entry['currency_id'],
                'currency_code'    => $entry['currency_code'],
            ];
        }

        return response()->json($result);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/insight/insightIncomeRevenue
     */
    public function revenue(GenericRequest $request): JsonResponse
    {
        $start           = $request->getStart();
        $end             = $request->getEnd();
        $assetAccounts   = $request->getAssetAccounts();
        $revenueAccounts = $request->getRevenueAccounts();
        $income          = $this->opsRepository->sumIncomeBySource($start, $end, $assetAccounts, $revenueAccounts);
        $result          = [];

        /** @var array $entry */
        foreach ($income as $entry) {
            $result[] = [
                'id'               => (string) $entry['id'],
                'name'             => $entry['name'],
                'difference'       => $entry['sum'],
                'difference_float' => (float) $entry['sum'], // float but on purpose.
                'currency_id'      => (string) $entry['currency_id'],
                'currency_code'    => $entry['currency_code'],
            ];
        }

        return response()->json($result);
    }
}
