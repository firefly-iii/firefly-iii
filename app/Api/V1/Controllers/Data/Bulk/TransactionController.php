<?php

/*
 * TransactionController.php
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

namespace FireflyIII\Api\V1\Controllers\Data\Bulk;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\Bulk\TransactionRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\AccountDestroyService;
use Illuminate\Http\JsonResponse;

/**
 * Class TransactionController
 *
 * Endpoint to update transactions by submitting
 * (optional) a "where" clause and an "update"
 * clause.
 *
 * Because this is a security nightmare waiting to happen validation
 * is pretty strict.
 */
class TransactionController extends Controller
{
    private AccountRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/data/bulkUpdateTransactions
     */
    public function update(TransactionRequest $request): JsonResponse
    {
        $query  = $request->getAll();
        $params = $query['query'];

        // this deserves better code, but for now a loop of basic if-statements
        // to respond to what is in the $query.
        // this is OK because only one thing can be in the query at the moment.
        if ($this->isUpdateTransactionAccount($params)) {
            $original    = $this->repository->find((int) $params['where']['account_id']);
            $destination = $this->repository->find((int) $params['update']['account_id']);

            /** @var AccountDestroyService $service */
            $service     = app(AccountDestroyService::class);
            $service->moveTransactions($original, $destination);
        }

        return response()->json([], 204);
    }

    /**
     * @param array<string, array<string, string>> $params
     */
    private function isUpdateTransactionAccount(array $params): bool
    {
        return array_key_exists('account_id', $params['where']) && array_key_exists('account_id', $params['update']);
    }
}
