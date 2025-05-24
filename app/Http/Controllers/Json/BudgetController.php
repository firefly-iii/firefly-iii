<?php

/**
 * BudgetController.php
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

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Models\AvailableBudget;
use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;

/**
 * Class BudgetController
 */
class BudgetController extends Controller
{
    use DateCalculation;

    private AvailableBudgetRepositoryInterface $abRepository;
    private BudgetLimitRepositoryInterface     $blRepository;
    private BudgetRepositoryInterface          $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-pie-chart');
                $this->repository   = app(BudgetRepositoryInterface::class);
                $this->abRepository = app(AvailableBudgetRepositoryInterface::class);
                $this->blRepository = app(BudgetLimitRepositoryInterface::class);
                $this->repository->cleanupBudgets();

                return $next($request);
            }
        );
    }

    public function getBudgetInformation(TransactionCurrency $currency, Carbon $start, Carbon $end): JsonResponse
    {
        $budgeted        = $this->blRepository->budgeted($start, $end, $currency);
        $availableBudget = $this->abRepository->getByCurrencyDate($start, $end, $currency);
        $available       = '0';
        $percentage      = '0';

        if ($availableBudget instanceof AvailableBudget) {
            $available = $availableBudget->amount;
            if (0 !== bccomp($available, '0')) {
                $percentage = bcmul(bcdiv($budgeted, $available), '100');
            }
        }

        // if available, get the AB for this period + currency, so the bar can be redrawn.
        return response()->json(
            [
                'budgeted'                => $budgeted,
                'budgeted_formatted'      => app('amount')->formatAnything($currency, $budgeted, true),
                'available'               => $available,
                'available_formatted'     => app('amount')->formatAnything($currency, $available, true),
                'percentage'              => $percentage,
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_name'           => $currency->name,
                'currency_decimal_places' => $currency->decimal_places,
            ]
        );
    }
}
