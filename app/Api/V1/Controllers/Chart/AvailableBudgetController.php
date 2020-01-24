<?php

/**
 * AvailableBudgetController.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Chart;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class AvailableBudgetController
 */
class AvailableBudgetController extends Controller
{
    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var BudgetRepositoryInterface */
    private $repository;

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
                $user                = auth()->user();
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->repository->setUser($user);
                $this->opsRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param AvailableBudget $availableBudget
     *
     * @return JsonResponse
     */
    public function overview(AvailableBudget $availableBudget): JsonResponse
    {
        $currency          = $availableBudget->transactionCurrency;
        $budgets           = $this->repository->getActiveBudgets();
        $budgetInformation = $this->opsRepository->spentInPeriodMc($budgets, new Collection, $availableBudget->start_date, $availableBudget->end_date);
        $spent             = 0.0;

        // get for current currency
        foreach ($budgetInformation as $spentInfo) {
            if ($spentInfo['currency_id'] === $availableBudget->transaction_currency_id) {
                $spent = $spentInfo['amount'];
            }
        }
        $left = bcadd($availableBudget->amount, (string)$spent);
        // left less than zero? Set to zero.
        if (bccomp($left, '0') === -1) {
            $left = '0';
        }

        $chartData = [
            [
                'label'                   => trans('firefly.spent'),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'type'                    => 'pie',
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => [$spent * -1],
            ],
            [
                'label'                   => trans('firefly.left'),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => [round($left, $currency->decimal_places)],
            ],
        ];

        return response()->json($chartData);
    }

}
