<?php
/**
 * RenderPartialViews.php
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

namespace FireflyIII\Support\Http\Controllers;


use FireflyIII\Helpers\Collection\BalanceLine;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Trait RenderPartialViews
 *
 */
trait RenderPartialViews
{
    /**
     * Get options for account report.
     *
     * @return string
     */
    protected function accountReportOptions(): string // render a view
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $expense    = $repository->getActiveAccountsByType([AccountType::EXPENSE]);
        $revenue    = $repository->getActiveAccountsByType([AccountType::REVENUE]);
        $set        = new Collection;
        $names      = $revenue->pluck('name')->toArray();
        foreach ($expense as $exp) {
            if (\in_array($exp->name, $names, true)) {
                $set->push($exp);
            }
        }
        try {
            $result = view('reports.options.account', compact('set'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.options.tag: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }

        return $result;
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
    protected function balanceAmount(array $attributes): string // generate view for report.
    {
        $role = (int)$attributes['role'];
        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var PopupReportInterface $popupHelper */
        $popupHelper = app(PopupReportInterface::class);

        $budget  = $budgetRepository->findNull((int)$attributes['budgetId']);
        $account = $accountRepository->findNull((int)$attributes['accountId']);


        switch (true) {
            case BalanceLine::ROLE_DEFAULTROLE === $role && null !== $budget && null !== $account:
                // normal row with a budget:
                $journals = $popupHelper->balanceForBudget($budget, $account, $attributes);
                break;
            case BalanceLine::ROLE_DEFAULTROLE === $role && null === $budget && null !== $account:
                // normal row without a budget:
                $budget = new Budget;
                $journals     = $popupHelper->balanceForNoBudget($account, $attributes);
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
     * Get options for budget report.
     *
     * @return string
     */
    protected function budgetReportOptions(): string // render a view
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getBudgets();
        try {
            $result = view('reports.options.budget', compact('budgets'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.options.tag: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }

        return $result;
    }

    /**
     * View for spent in a single budget.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function budgetSpentAmount(array $attributes): string // generate view for report.
    {
        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);

        /** @var PopupReportInterface $popupHelper */
        $popupHelper = app(PopupReportInterface::class);

        $budget = $budgetRepository->findNull((int)$attributes['budgetId']);
        if (null === $budget) {
            $budget = new Budget;
        }
        $journals = $popupHelper->byBudget($budget, $attributes);
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
    protected function categoryEntry(array $attributes): string // generate view for report.
    {
        /** @var PopupReportInterface $popupHelper */
        $popupHelper = app(PopupReportInterface::class);

        /** @var CategoryRepositoryInterface $categoryRepository */
        $categoryRepository = app(CategoryRepositoryInterface::class);
        $category           = $categoryRepository->findNull((int)$attributes['categoryId']);

        if (null === $category) {
            return 'This is an unknown category. Apologies.';
        }

        $journals = $popupHelper->byCategory($category, $attributes);
        try {
            $view = view('popup.report.category-entry', compact('journals', 'category'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';
        }

        return $view;
    }

    /**
     * Get options for category report.
     *
     * @return string
     */
    protected function categoryReportOptions(): string // render a view
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        try {
            $result = view('reports.options.category', compact('categories'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.options.category: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }

        return $result;
    }

    /**
     * Returns all the expenses that went to the given expense account.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function expenseEntry(array $attributes): string // generate view for report.
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var PopupReportInterface $popupHelper */
        $popupHelper = app(PopupReportInterface::class);

        $account = $accountRepository->findNull((int)$attributes['accountId']);

        if (null === $account) {
            return 'This is an unknown account. Apologies.';
        }

        $journals = $popupHelper->byExpenses($account, $attributes);
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
    protected function incomeEntry(array $attributes): string // generate view for report.
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var PopupReportInterface $popupHelper */
        $popupHelper = app(PopupReportInterface::class);

        $account = $accountRepository->findNull((int)$attributes['accountId']);

        if (null === $account) {
            return 'This is an unknown category. Apologies.';
        }

        $journals = $popupHelper->byIncome($account, $attributes);
        try {
            $view = view('popup.report.income-entry', compact('journals', 'account'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';
        }

        return $view;
    }

    /**
     * Get options for default report.
     *
     * @return string
     */
    protected function noReportOptions(): string // render a view
    {
        try {
            $result = view('reports.options.no-options')->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.options.no-options: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }

        return $result;
    }

    /**
     * Get options for tag report.
     *
     * @return string
     */
    protected function tagReportOptions(): string // render a view
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $tags       = $repository->get()->sortBy(
            function (Tag $tag) {
                return $tag->tag;
            }
        );
        try {
            $result = view('reports.options.tag', compact('tags'))->render();
        } catch (Throwable $e) {
            Log::error(sprintf('Cannot render reports.options.tag: %s', $e->getMessage()));
            $result = 'Could not render view.';
        }

        return $result;
    }
}