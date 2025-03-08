<?php

/*
 * BalanceController.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Chart;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Chart\ChartRequest;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Chart\ChartData;
use FireflyIII\Support\Http\Api\AccountBalanceGrouped;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\CollectsAccountsFromFilter;
use Illuminate\Http\JsonResponse;

/**
 * Class BalanceController
 */
class BalanceController extends Controller
{
    use CleansChartData;
    use CollectsAccountsFromFilter;

    private ChartData                  $chartData;
    private GroupCollectorInterface    $collector;
    private AccountRepositoryInterface $repository;

    // private TransactionCurrency        $default;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->collector  = app(GroupCollectorInterface::class);
                $userGroup        = $this->validateUserGroup($request);
                $this->repository->setUserGroup($userGroup);
                $this->collector->setUserGroup($userGroup);
                $this->chartData  = new ChartData();
                // $this->default    = app('amount')->getNativeCurrency();

                return $next($request);
            }
        );
    }

    /**
     * The code is practically a duplicate of ReportController::operations.
     *
     * Currency is up to the account/transactions in question, but conversion to the default
     * currency is possible.
     *
     * If the transaction being processed is already in native currency OR if the
     * foreign amount is in the native currency, the amount will not be converted.
     *
     * @throws FireflyException
     */
    public function balance(ChartRequest $request): JsonResponse
    {
        $queryParameters = $request->getParameters();
        $accounts        = $this->getAccountList($queryParameters);

        // prepare for currency conversion and data collection:
        /** @var TransactionCurrency $default */
        $default         = app('amount')->getNativeCurrency();

        // get journals for entire period:

        $this->collector->setRange($queryParameters['start'], $queryParameters['end'])
            ->withAccountInformation()
            ->setXorAccounts($accounts)
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::RECONCILIATION->value, TransactionTypeEnum::TRANSFER->value])
        ;
        $journals        = $this->collector->getExtractedJournals();

        $object          = new AccountBalanceGrouped();
        $object->setPreferredRange($queryParameters['period']);
        $object->setDefault($default);
        $object->setAccounts($accounts);
        $object->setJournals($journals);
        $object->setStart($queryParameters['start']);
        $object->setEnd($queryParameters['end']);
        $object->groupByCurrencyAndPeriod();
        $data            = $object->convertToChartData();
        foreach ($data as $entry) {
            $this->chartData->add($entry);
        }

        return response()->json($this->chartData->render());
    }
}
