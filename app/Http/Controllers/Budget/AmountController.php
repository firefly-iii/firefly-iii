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
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BudgetIncomeRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use View;

/**
 *
 * Class AmountController
 */
class AmountController extends Controller
{
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        View::share('hideBudgets', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-tasks');
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * @param Request                   $request
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return JsonResponse
     */
    public function amount(Request $request, BudgetRepositoryInterface $repository, Budget $budget): JsonResponse
    {
        $amount      = (string)$request->get('amount');
        $start       = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end         = Carbon::createFromFormat('Y-m-d', $request->get('end'));
        $budgetLimit = $this->repository->updateLimitAmount($budget, $start, $end, $amount);
        $largeDiff   = false;
        $warnText    = '';
        $days        = 0;
        $daysInMonth = 0;
        if (0 === bccomp($amount, '0')) {
            $budgetLimit = null;
        }

        // if today is between start and end, use the diff in days between end and today (days left)
        // otherwise, use diff between start and end.
        $today = new Carbon;
        Log::debug(sprintf('Start is %s, end is %s, today is %s', $start->format('Y-m-d'), $end->format('Y-m-d'), $today->format('Y-m-d')));
        if ($today->gte($start) && $today->lte($end)) {
            $days        = $end->diffInDays($today);
            $daysInMonth = $start->diffInDays($today);
        }
        if ($today->lte($start) || $today->gte($end)) {
            $days        = $start->diffInDays($end);
            $daysInMonth = $start->diffInDays($end);
        }
        $days        = 0 === $days ? 1 : $days;
        $daysInMonth = 0 === $daysInMonth ? 1 : $daysInMonth;

        // calculate left in budget:
        $spent      = $repository->spentInPeriod(new Collection([$budget]), new Collection, $start, $end);
        $currency   = app('amount')->getDefaultCurrency();
        $left       = app('amount')->formatAnything($currency, bcadd($amount, $spent), true);
        $leftPerDay = 'none';

        // is user has money left, calculate.
        if (1 === bccomp(bcadd($amount, $spent), '0')) {
            $leftPerDay = app('amount')->formatAnything($currency, bcdiv(bcadd($amount, $spent), (string)$days), true);
        }


        // over or under budgeting, compared to previous budgets?
        $average = $this->repository->budgetedPerDay($budget);
        // current average per day:
        $diff    = $start->diffInDays($end);
        $current = $amount;
        if ($diff > 0) {
            $current = bcdiv($amount, (string)$diff);
        }
        if (bccomp(bcmul('1.1', $average), $current) === -1) {
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
                'daysInMonth'  => $daysInMonth,

            ]
        );
    }


    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function infoIncome(Carbon $start, Carbon $end)
    {
        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('info-income');

        Log::debug(sprintf('infoIncome start is %s', $start->format('Y-m-d')));
        Log::debug(sprintf('infoIncome end is %s', $end->format('Y-m-d')));

        if ($cache->has()) {
            // @codeCoverageIgnoreStart
            $result = $cache->get();

            return view('budgets.info', compact('result'));
            // @codeCoverageIgnoreEnd
        }
        $result   = [
            'available' => '0',
            'earned'    => '0',
            'suggested' => '0',
        ];
        $currency = app('amount')->getDefaultCurrency();
        $range    = Preferences::get('viewRange', '1M')->data;
        /** @var Carbon $begin */
        $begin = app('navigation')->subtractPeriod($start, $range, 3);

        Log::debug(sprintf('Range is %s', $range));
        Log::debug(sprintf('infoIncome begin is %s', $begin->format('Y-m-d')));

        // get average amount available.
        $total        = '0';
        $count        = 0;
        $currentStart = clone $begin;
        while ($currentStart < $start) {

            Log::debug(sprintf('Loop: currentStart is %s', $currentStart->format('Y-m-d')));
            $currentEnd   = app('navigation')->endOfPeriod($currentStart, $range);
            $total        = bcadd($total, $this->repository->getAvailableBudget($currency, $currentStart, $currentEnd));
            $currentStart = app('navigation')->addPeriod($currentStart, $range, 0);
            ++$count;
        }
        Log::debug('Loop end');

        if (0 === $count) {
            $count = 1;
        }
        $result['available'] = bcdiv($total, (string)$count);

        // amount earned in this period:
        $subDay = clone $end;
        $subDay->subDay();
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($begin, $subDay)->setTypes([TransactionType::DEPOSIT])->withOpposingAccount();
        $result['earned'] = bcdiv((string)$collector->getJournals()->sum('transaction_amount'), (string)$count);

        // amount spent in period
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($begin, $subDay)->setTypes([TransactionType::WITHDRAWAL])->withOpposingAccount();
        $result['spent'] = bcdiv((string)$collector->getJournals()->sum('transaction_amount'), (string)$count);
        // suggestion starts with the amount spent
        $result['suggested'] = bcmul($result['spent'], '-1');
        $result['suggested'] = 1 === bccomp($result['suggested'], $result['earned']) ? $result['earned'] : $result['suggested'];
        // unless it's more than you earned. So min() of suggested/earned

        $cache->store($result);

        return view('budgets.info', compact('result', 'begin', 'currentEnd'));
    }


    /**
     * @param BudgetIncomeRequest $request
     *
     * @return RedirectResponse
     */
    public function postUpdateIncome(BudgetIncomeRequest $request): RedirectResponse
    {
        $start           = Carbon::createFromFormat('Y-m-d', $request->string('start'));
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

        return view('budgets.income', compact('available', 'start', 'end', 'page'));
    }

}