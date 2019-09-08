<?php
/**
 * IndexController.php
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
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class IndexController
 */
class IndexController extends Controller
{

    use DateCalculation;
    /** @var AvailableBudgetRepositoryInterface */
    private $abRepository;
    /** @var BudgetLimitRepositoryInterface */
    private $blRepository;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepository;
    /** @var OperationsRepositoryInterface */
    private $opsRepository;
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * IndexController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-tasks');
                $this->repository         = app(BudgetRepositoryInterface::class);
                $this->opsRepository      = app(OperationsRepositoryInterface::class);
                $this->abRepository       = app(AvailableBudgetRepositoryInterface::class);
                $this->currencyRepository = app(CurrencyRepositoryInterface::class);
                $this->blRepository       = app(BudgetLimitRepositoryInterface::class);
                $this->repository->cleanupBudgets();

                return $next($request);
            }
        );
    }

    /**
     * TODO the "budgeted" progress bar doesn't update.
     * Show all budgets.
     *
     * @param Request     $request
     *
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, Carbon $start = null, Carbon $end = null)
    {
        // collect some basic vars:
        $range           = app('preferences')->get('viewRange', '1M')->data;
        $start           = $start ?? session('start', Carbon::now()->startOfMonth());
        $end             = $end ?? app('navigation')->endOfPeriod($start, $range);
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $budgeted        = '0';
        $spent           = '0';


        // new period stuff:
        $periodTitle = app('navigation')->periodShow($start, $range);
        $prevLoop    = $this->getPreviousPeriods($start, $range);
        $nextLoop    = $this->getNextPeriods($start, $range);

        // get all available budgets.
        $ab               = $this->abRepository->get($start, $end);
        $availableBudgets = [];
        // for each, complement with spent amount:
        /** @var AvailableBudget $entry */
        foreach ($ab as $entry) {
            $array               = $entry->toArray();
            $array['start_date'] = $entry->start_date;
            $array['end_date']   = $entry->end_date;

            // spent in period:
            $spentArr       = $this->opsRepository->sumExpenses($entry->start_date, $entry->end_date, null, null, $entry->transactionCurrency);
            $array['spent'] = $spentArr[$entry->transaction_currency_id]['sum'] ?? '0';

            // budgeted in period:
            $budgeted           = $this->blRepository->budgeted($entry->start_date, $entry->end_date, $entry->transactionCurrency,);
            $array['budgeted']  = $budgeted;
            $availableBudgets[] = $array;
            unset($spentArr);
        }

        if (0 === count($availableBudgets)) {
            // get budgeted for default currency:
            $budgeted = $this->blRepository->budgeted($start, $end, $defaultCurrency,);
            $spentArr = $this->opsRepository->sumExpenses($start, $end, null, null, $defaultCurrency);
            $spent    = $spentArr[$defaultCurrency->id]['sum'] ?? '0';
            unset($spentArr);
        }

        // count the number of enabled currencies. This determines if we display a "+" button.
        $currencies      = $this->currencyRepository->getEnabled();
        $enableAddButton = $currencies->count() > count($availableBudgets);

        // number of days for consistent budgeting.
        $activeDaysPassed = $this->activeDaysPassed($start, $end); // see method description.
        $activeDaysLeft   = $this->activeDaysLeft($start, $end); // see method description.
        Log::debug(sprintf('Start: %s, end: %s', $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));

        // get all budgets, and paginate them into $budgets.
        $collection = $this->repository->getActiveBudgets();
        $budgets    = [];

        // complement budget with budget limits in range, and expenses in currency X in range.
        /** @var Budget $current */
        foreach ($collection as $current) {
            $array             = $current->toArray();
            $array['spent']    = [];
            $array['budgeted'] = [];
            $budgetLimits      = $this->blRepository->getBudgetLimits($current, $start, $end);

            /** @var BudgetLimit $limit */
            foreach ($budgetLimits as $limit) {
                $currency = $limit->transactionCurrency ?? $defaultCurrency;
                $array['budgeted'][] = [
                    'id'                      => $limit->id,
                    'amount'                  => $limit->amount,
                    'currency_id'             => $currency->id,
                    'currency_symbol'         => $currency->symbol,
                    'currency_name'           => $currency->name,
                    'currency_decimal_places' => $currency->decimal_places,
                ];
            }

            /** @var TransactionCurrency $currency */
            foreach ($currencies as $currency) {
                $spentArr = $this->opsRepository->sumExpenses($start, $end, null, new Collection([$current]), $currency);
                if (isset($spentArr[$currency->id]['sum'])) {
                    $array['spent'][$currency->id]['spent']                   = $spentArr[$currency->id]['sum'];
                    $array['spent'][$currency->id]['currency_id']             = $currency->id;
                    $array['spent'][$currency->id]['currency_symbol']         = $currency->symbol;
                    $array['spent'][$currency->id]['currency_decimal_places'] = $currency->decimal_places;

                }
            }
            $budgets[] = $array;
        }

        // get all inactive budgets, and simply list them:
        $inactive = $this->repository->getInactiveBudgets();


        return view(
            'budgets.index', compact(
                               'availableBudgets',
                               'budgeted', 'spent',
                               'prevLoop', 'nextLoop',
                               'budgets',
                               'currencies',
                               'enableAddButton',
                               'periodTitle',
                               'defaultCurrency',
                               'activeDaysPassed', 'activeDaysLeft',
                               'inactive', 'budgets', 'start', 'end'
                           )
        );
    }

    /**
     * @param Request                   $request
     *
     * @param BudgetRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function reorder(Request $request, BudgetRepositoryInterface $repository): JsonResponse
    {
        $budgetIds = $request->get('budgetIds');

        foreach ($budgetIds as $index => $budgetId) {
            $budgetId = (int)$budgetId;
            $budget   = $repository->findNull($budgetId);
            if (null !== $budget) {
                Log::debug(sprintf('Set budget #%d ("%s") to position %d', $budget->id, $budget->name, $index + 1));
                $repository->setBudgetOrder($budget, $index + 1);
            }
        }

        return response()->json(['OK']);
    }


}
