<?php

/*
 * BillController.php
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

namespace FireflyIII\Api\V1\Controllers\Insight\Expense;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Insight\GenericRequest;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class BillController
 */
class BillController extends Controller
{
    private BillRepositoryInterface $repository;

    /**
     * BillController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $user             = auth()->user();
                $this->repository = app(BillRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/insight/insightExpenseBill
     *
     * Expenses per bill, possibly filtered by bill and account.
     */
    public function bill(GenericRequest $request): JsonResponse
    {
        $accounts   = $request->getAssetAccounts();
        $bills      = $request->getBills();
        $start      = $request->getStart();
        $end        = $request->getEnd();
        $response   = [];

        // get all bills:
        if (0 === $bills->count()) {
            $bills = $this->repository->getBills();
        }

        // collect all expenses in this period (regardless of type) by the given bills and accounts.
        $collector  = app(GroupCollectorInterface::class);
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value])->setRange($start, $end)->setSourceAccounts($accounts);
        $collector->setBills($bills);

        $genericSet = $collector->getExtractedJournals();
        foreach ($genericSet as $journal) {
            $billId            = (int) $journal['bill_id'];
            $currencyId        = (int) $journal['currency_id'];
            $foreignCurrencyId = (int) $journal['foreign_currency_id'];
            $key               = sprintf('%d-%d', $billId, $currencyId);
            $foreignKey        = sprintf('%d-%d', $billId, $foreignCurrencyId);

            if (0 !== $currencyId) {
                $response[$key] ??= [
                    'id'               => (string) $billId,
                    'name'             => $journal['bill_name'],
                    'difference'       => '0',
                    'difference_float' => 0,
                    'currency_id'      => (string) $currencyId,
                    'currency_code'    => $journal['currency_code'],
                ];
                $response[$key]['difference']       = bcadd($response[$key]['difference'], $journal['amount']);
                $response[$key]['difference_float'] = (float) $response[$key]['difference']; // intentional float
            }
            if (0 !== $foreignCurrencyId) {
                $response[$foreignKey] ??= [
                    'difference'       => '0',
                    'difference_float' => 0,
                    'currency_id'      => (string) $foreignCurrencyId,
                    'currency_code'    => $journal['foreign_currency_code'],
                ];
                $response[$foreignKey]['difference']       = bcadd($response[$foreignKey]['difference'], $journal['foreign_amount']);
                $response[$foreignKey]['difference_float'] = (float) $response[$foreignKey]['difference']; // intentional float
            }
        }

        return response()->json(array_values($response));
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/insight/insightExpenseNoBill
     *
     * Expenses for no bill filtered by account.
     */
    public function noBill(GenericRequest $request): JsonResponse
    {
        $accounts   = $request->getAssetAccounts();
        $start      = $request->getStart();
        $end        = $request->getEnd();
        $response   = [];

        // collect all expenses in this period (regardless of type) by the given bills and accounts.
        $collector  = app(GroupCollectorInterface::class);
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value])->setRange($start, $end)->setSourceAccounts($accounts);
        $collector->withoutBill();

        $genericSet = $collector->getExtractedJournals();

        foreach ($genericSet as $journal) {
            $currencyId        = (int) $journal['currency_id'];
            $foreignCurrencyId = (int) $journal['foreign_currency_id'];

            if (0 !== $currencyId) {
                $response[$currencyId] ??= [
                    'difference'       => '0',
                    'difference_float' => 0,
                    'currency_id'      => (string) $currencyId,
                    'currency_code'    => $journal['currency_code'],
                ];
                $response[$currencyId]['difference']       = bcadd($response[$currencyId]['difference'], $journal['amount']);
                $response[$currencyId]['difference_float'] = (float) $response[$currencyId]['difference']; // intentional float
            }
            if (0 !== $foreignCurrencyId) {
                $response[$foreignCurrencyId] ??= [
                    'difference'       => '0',
                    'difference_float' => 0,
                    'currency_id'      => (string) $foreignCurrencyId,
                    'currency_code'    => $journal['foreign_currency_code'],
                ];
                $response[$foreignCurrencyId]['difference']       = bcadd($response[$foreignCurrencyId]['difference'], $journal['foreign_amount']);
                $response[$foreignCurrencyId]['difference_float'] = (float) $response[$foreignCurrencyId]['difference']; // intentional float
            }
        }

        return response()->json(array_values($response));
    }
}
