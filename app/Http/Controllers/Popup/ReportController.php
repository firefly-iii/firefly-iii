<?php
/**
 * ReportController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Popup;

use Carbon\Carbon;
use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\Binder\AccountList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use InvalidArgumentException;
use Log;
use Throwable;

/**
 * Class ReportController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportController extends Controller
{
    /** @var AccountRepositoryInterface The account repository */
    private $accountRepository;
    /** @var BudgetRepositoryInterface The budget repository */
    private $budgetRepository;
    /** @var CategoryRepositoryInterface The category repository */
    private $categoryRepository;
    /** @var PopupReportInterface Various helper functions. */
    private $popupHelper;

    /**
     * ReportController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var AccountRepositoryInterface accountRepository */
                $this->accountRepository = app(AccountRepositoryInterface::class);

                /** @var BudgetRepositoryInterface budgetRepository */
                $this->budgetRepository = app(BudgetRepositoryInterface::class);

                /** @var CategoryRepositoryInterface categoryRepository */
                $this->categoryRepository = app(CategoryRepositoryInterface::class);

                /** @var PopupReportInterface popupHelper */
                $this->popupHelper = app(PopupReportInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Generate popup view.
     * @param Request $request
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function general(Request $request): JsonResponse
    {
        $attributes = $request->get('attributes') ?? [];
        $attributes = $this->parseAttributes($attributes);

        app('view')->share('start', $attributes['startDate']);
        app('view')->share('end', $attributes['endDate']);

        switch ($attributes['location']) {
            default:
                $html = sprintf('Firefly III cannot handle "%s"-popups.', $attributes['location']);
                break;
            case 'budget-spent-amount':
                $html = $this->budgetSpentAmount($attributes);
                break;
            case 'expense-entry':
                $html = $this->expenseEntry($attributes);
                break;
            case 'income-entry':
                $html = $this->incomeEntry($attributes);
                break;
            case 'category-entry':
                $html = $this->categoryEntry($attributes);
                break;
            case 'balance-amount':
                $html = $this->balanceAmount($attributes);
                break;
        }

        return response()->json(['html' => $html]);
    }

    /**
     * View for balance row.
     *
     * @param array $attributes
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function balanceAmount(array $attributes): string
    {
        $role    = (int)$attributes['role'];
        $budget  = $this->budgetRepository->findNull((int)$attributes['budgetId']);
        $account = $this->accountRepository->findNull((int)$attributes['accountId']);


        switch (true) {
            case BalanceLine::ROLE_DEFAULTROLE === $role && null !== $budget && null !== $account:
                // normal row with a budget:
                $journals = $this->popupHelper->balanceForBudget($budget, $account, $attributes);
                break;
            case BalanceLine::ROLE_DEFAULTROLE === $role && null === $budget && null !== $account:
                // normal row without a budget:
                $journals     = $this->popupHelper->balanceForNoBudget($account, $attributes);
                $budget->name = (string)trans('firefly.no_budget');
                break;
            case BalanceLine::ROLE_TAGROLE === $role:
                // row with tag info.
                return 'Firefly cannot handle this type of info-button (BalanceLine::TagRole)';
        }
        try {
            $view = view('popup.report.balance-amount', compact('journals', 'budget', 'account'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';
        }

        return $view;
    }

    /**
     * View for spent in a single budget.
     *
     * @param array $attributes
     *
     * @return string
     */
    private function budgetSpentAmount(array $attributes): string
    {
        $budget = $this->budgetRepository->findNull((int)$attributes['budgetId']);
        if (null === $budget) {
            return 'This is an unknown budget. Apologies.';
        }
        $journals = $this->popupHelper->byBudget($budget, $attributes);
        try {
            $view = view('popup.report.budget-spent-amount', compact('journals', 'budget'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';
        }

        return $view;
    }

    /**
     * View for transactions in a category.
     *
     * @param array $attributes
     *
     * @return string
     */
    private function categoryEntry(array $attributes): string
    {
        $category = $this->categoryRepository->findNull((int)$attributes['categoryId']);

        if (null === $category) {
            return 'This is an unknown category. Apologies.';
        }

        $journals = $this->popupHelper->byCategory($category, $attributes);
        try {
            $view = view('popup.report.category-entry', compact('journals', 'category'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';
        }

        return $view;
    }

    /**
     * Returns all the expenses that went to the given expense account.
     *
     * @param array $attributes
     *
     * @return string
     */
    private function expenseEntry(array $attributes): string
    {
        $account = $this->accountRepository->findNull((int)$attributes['accountId']);

        if (null === $account) {
            return 'This is an unknown account. Apologies.';
        }

        $journals = $this->popupHelper->byExpenses($account, $attributes);
        try {
            $view = view('popup.report.expense-entry', compact('journals', 'account'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';
        }

        return $view;
    }

    /**
     * Returns all the incomes that went to the given asset account.
     *
     * @param array $attributes
     *
     * @return string
     */
    private function incomeEntry(array $attributes): string
    {
        $account = $this->accountRepository->findNull((int)$attributes['accountId']);

        if (null === $account) {
            return 'This is an unknown category. Apologies.';
        }

        $journals = $this->popupHelper->byIncome($account, $attributes);
        try {
            $view = view('popup.report.income-entry', compact('journals', 'account'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';
        }

        return $view;
    }

    /**
     * Parses attributes from URI.
     *
     * @param array $attributes
     *
     * @return array
     */
    private function parseAttributes(array $attributes): array
    {
        $attributes['location'] = $attributes['location'] ?? '';
        $attributes['accounts'] = AccountList::routeBinder($attributes['accounts'] ?? '', new Route('get', '', []));
        try {
            $attributes['startDate'] = Carbon::createFromFormat('Ymd', $attributes['startDate']);
        } catch (InvalidArgumentException $e) {
            Log::debug(sprintf('Not important error message: %s', $e->getMessage()));
            $date = new Carbon;
            $date->startOfMonth();
            $attributes['startDate'] = $date;
        }

        try {
            $attributes['endDate'] = Carbon::createFromFormat('Ymd', $attributes['endDate']);
        } catch (InvalidArgumentException $e) {
            Log::debug('Not important error message: %s', $e->getMessage());
            $date = new Carbon;
            $date->startOfMonth();
            $attributes['endDate'] = $date;
        }

        return $attributes;
    }
}
