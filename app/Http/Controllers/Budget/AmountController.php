<?php
/**
 * AmountController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Budget;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BudgetIncomeRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class AmountController
 */
class AmountController extends Controller
{
    use DateCalculation;

    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * AmountController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        app('view')->share('hideBudgets', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-tasks');
                $this->repository    = app(BudgetRepositoryInterface::class);
                $this->opsRepository = app(OperationsRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * Set the amount for a single budget in a specific period.
     *
     * @param Request $request
     * @param Budget  $budget
     *
     * @return JsonResponse
     */
    public function amount(Request $request, Budget $budget): JsonResponse
    {
        // grab vars from URI
        $amount = (string)$request->get('amount');

        /** @var Carbon $start */
        $start = Carbon::createFromFormat('Y-m-d', $request->get('start'));

        /** @var Carbon $end */
        $end = Carbon::createFromFormat('Y-m-d', $request->get('end'));

        // grab other useful vars
        $currency       = app('amount')->getDefaultCurrency();
        $activeDaysLeft = $this->activeDaysLeft($start, $end);
        $periodLength   = $start->diffInDays($end) + 1; // absolute period length.

        // update limit amount:
        $budgetLimit = $this->repository->updateLimitAmount($budget, $start, $end, $amount);

        // calculate what the user has spent in current period.
        $spent = $this->repository->spentInPeriod(new Collection([$budget]), new Collection, $start, $end);

        // given the new budget, this is what they have left (and left per day?)
        $left       = app('amount')->formatAnything($currency, bcadd($amount, $spent), true);
        $leftPerDay = null;

        // If the user budgets ANY amount per day for this budget (anything but zero) Firefly III calculates how much he could spend per day.
        if (1 === bccomp(bcadd($amount, $spent), '0')) {
            $leftPerDay = app('amount')->formatAnything($currency, bcdiv(bcadd($amount, $spent), (string)$activeDaysLeft), true);
        }

        $largeDiff = false;
        $warnText  = '';

        // Get the average amount of money the user budgets for this budget. And calculate the same for the current amount.
        // If the difference is very large, give the user a notification.
        $average = $this->opsRepository->budgetedPerDay($budget);
        $current = bcdiv($amount, (string)$periodLength);
        if (bccomp(bcmul('1.3', $average), $current) === -1) {
            $largeDiff = true;
            $warnText  = (string)trans(
                'firefly.over_budget_warn',
                [
                    'amount'      => app('amount')->formatAnything($currency, $average, false),
                    'over_amount' => app('amount')->formatAnything($currency, $current, false),
                ]
            );
        }

        app('preferences')->mark();

        return response()->json(
            [
                'left'         => $left,
                'name'         => $budget->name,
                'limit'        => $budgetLimit ? $budgetLimit->id : 0,
                'amount'       => $amount,
                'current'      => $current,
                'average'      => $average,
                'large_diff'   => $largeDiff,
                'left_per_day' => $leftPerDay,
                'warn_text'    => $warnText,
            ]
        );
    }

    /**
     * Store an available budget for the current period.
     *
     * @param BudgetIncomeRequest $request
     *
     * @return RedirectResponse
     */
    public function postUpdateIncome(BudgetIncomeRequest $request): RedirectResponse
    {
        /** @var Carbon $start */
        $start = Carbon::createFromFormat('Y-m-d', $request->string('start'));
        /** @var Carbon $end */
        $end             = Carbon::createFromFormat('Y-m-d', $request->string('end'));
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $amount          = $request->get('amount');
        $page            = 0 === $request->integer('page') ? 1 : $request->integer('page');
        $this->repository->cleanupBudgets();
        $this->repository->setAvailableBudget($defaultCurrency, $start, $end, $amount);
        app('preferences')->mark();

        return redirect(route('budgets.index', [$start->format('Y-m-d')]) . '?page=' . $page);
    }

    /**
     * Shows the form to update available budget.
     *
     * @param Request $request
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function updateIncome(Request $request, Carbon $start, Carbon $end)
    {
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $available       = $this->repository->getAvailableBudget($defaultCurrency, $start, $end);
        $available       = round($available, $defaultCurrency->decimal_places);
        $page            = (int)$request->get('page');

        return view('budgets.income', compact('available', 'start', 'end', 'page', 'defaultCurrency'));
    }
}
