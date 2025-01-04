<?php

/**
 * RenderPartialViews.php
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

namespace FireflyIII\Support\Http\Controllers;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\Search\OperatorQuerySearch;

/**
 * Trait RenderPartialViews
 */
trait RenderPartialViews
{
    /**
     * View for transactions in a budget for an account.
     *
     * @throws FireflyException
     */
    protected function budgetEntry(array $attributes): string // generate view for report.
    {
        /** @var PopupReportInterface $popupHelper */
        $popupHelper      = app(PopupReportInterface::class);

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        $budget           = $budgetRepository->find((int) $attributes['budgetId']);

        $accountRepos     = app(AccountRepositoryInterface::class);
        $account          = $accountRepos->find((int) $attributes['accountId']);

        if (null === $budget || null === $account) {
            throw new FireflyException('Could not render popup.report.balance-amount because budget or account is null.');
        }

        $journals         = $popupHelper->balanceForBudget($budget, $account, $attributes);

        try {
            $view = view('popup.report.balance-amount', compact('journals', 'budget', 'account'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';

            throw new FireflyException($view, 0, $e);
        }

        return $view;
    }

    /**
     * Get options for budget report.
     *
     * @throws FireflyException
     */
    protected function budgetReportOptions(): string // render a view
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getActiveBudgets();

        try {
            $result = view('reports.options.budget', compact('budgets'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.options.tag: %s', $e->getMessage()));
            $result = 'Could not render view.';

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * View for spent in a single budget.
     *
     * @throws FireflyException
     */
    protected function budgetSpentAmount(array $attributes): string // generate view for report.
    {
        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);

        /** @var PopupReportInterface $popupHelper */
        $popupHelper      = app(PopupReportInterface::class);

        $budget           = $budgetRepository->find((int) $attributes['budgetId']);
        if (null === $budget) {
            // transactions without a budget.
            $budget = new Budget();
        }
        $journals         = $popupHelper->byBudget($budget, $attributes);

        try {
            $view = view('popup.report.budget-spent-amount', compact('journals', 'budget'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';

            throw new FireflyException($view, 0, $e);
        }

        return $view;
    }

    /**
     * View for transactions in a category.
     *
     * @throws FireflyException
     */
    protected function categoryEntry(array $attributes): string // generate view for report.
    {
        /** @var PopupReportInterface $popupHelper */
        $popupHelper        = app(PopupReportInterface::class);

        /** @var CategoryRepositoryInterface $categoryRepository */
        $categoryRepository = app(CategoryRepositoryInterface::class);
        $category           = $categoryRepository->find((int) $attributes['categoryId']);
        $journals           = $popupHelper->byCategory($category, $attributes);

        try {
            $view = view('popup.report.category-entry', compact('journals', 'category'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';

            throw new FireflyException($view, 0, $e);
        }

        return $view;
    }

    /**
     * Get options for category report.
     *
     * @throws FireflyException
     */
    protected function categoryReportOptions(): string // render a view
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();

        try {
            $result = view('reports.options.category', compact('categories'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.options.category: %s', $e->getMessage()));
            $result = 'Could not render view.';

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * Get options for double report.
     *
     * @throws FireflyException
     */
    protected function doubleReportOptions(): string // render a view
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $expense    = $repository->getActiveAccountsByType([AccountTypeEnum::EXPENSE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value]);
        $revenue    = $repository->getActiveAccountsByType([AccountTypeEnum::REVENUE->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value]);
        $set        = [];

        /** @var Account $account */
        foreach ($expense as $account) {
            // loop revenue, find same account:
            /** @var Account $otherAccount */
            foreach ($revenue as $otherAccount) {
                if (
                    (
                        ($otherAccount->name === $account->name)
                        || (null !== $account->iban && null !== $otherAccount->iban && $otherAccount->iban === $account->iban)
                    )
                    && $otherAccount->id !== $account->id
                ) {
                    $set[] = $account;
                }
            }
        }

        try {
            $result = view('reports.options.double', compact('set'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.options.tag: %s', $e->getMessage()));
            $result = 'Could not render view.';

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * Returns all the expenses that went to the given expense account.
     *
     * @throws FireflyException
     */
    protected function expenseEntry(array $attributes): string // generate view for report.
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var PopupReportInterface $popupHelper */
        $popupHelper       = app(PopupReportInterface::class);

        $account           = $accountRepository->find((int) $attributes['accountId']);

        if (null === $account) {
            return 'This is an unknown account. Apologies.';
        }

        $journals          = $popupHelper->byExpenses($account, $attributes);

        try {
            $view = view('popup.report.expense-entry', compact('journals', 'account'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';

            throw new FireflyException($view, 0, $e);
        }

        return $view;
    }

    /**
     * Get current (from system) rule actions.
     *
     * @throws FireflyException
     */
    protected function getCurrentActions(Rule $rule): array // get info from object and present.
    {
        $index          = 0;
        $actions        = [];
        // must be repos
        $currentActions = $rule->ruleActions()->orderBy('order', 'ASC')->get();

        /** @var RuleAction $entry */
        foreach ($currentActions as $entry) {
            $count = ($index + 1);

            try {
                $actions[] = view(
                    'rules.partials.action',
                    [
                        'oldAction'  => $entry->action_type,
                        'oldValue'   => $entry->action_value,
                        'oldChecked' => $entry->stop_processing,
                        'count'      => $count,
                    ]
                )->render();
            } catch (\Throwable $e) {
                app('log')->debug(sprintf('Throwable was thrown in getCurrentActions(): %s', $e->getMessage()));
                app('log')->error($e->getTraceAsString());

                throw new FireflyException(sprintf('Could not render: %s', $e->getMessage()), 0, $e);
            }

            ++$index;
        }

        return $actions;
    }

    /**
     * Get current (from DB) rule triggers.
     *
     * @throws FireflyException
     */
    protected function getCurrentTriggers(Rule $rule): array // get info from object and present.
    {
        // TODO duplicated code.
        $operators       = config('search.operators');
        $triggers        = [];
        foreach ($operators as $key => $operator) {
            if ('user_action' !== $key && false === $operator['alias']) {
                $triggers[$key] = (string) trans(sprintf('firefly.rule_trigger_%s_choice', $key));
            }
        }
        asort($triggers);
        $index           = 0;
        $renderedEntries = [];
        // must be repos
        $currentTriggers = $rule->ruleTriggers()->orderBy('order', 'ASC')->get();

        /** @var RuleTrigger $entry */
        foreach ($currentTriggers as $entry) {
            if ('user_action' !== $entry->trigger_type) {
                $count = ($index + 1);

                try {
                    $rootOperator      = OperatorQuerySearch::getRootOperator((string) $entry->trigger_type);
                    if (str_starts_with($rootOperator, '-')) {
                        $rootOperator = substr($rootOperator, 1);
                    }
                    $renderedEntries[] = view(
                        'rules.partials.trigger',
                        [
                            'oldTrigger'    => $rootOperator,
                            'oldValue'      => $entry->trigger_value,
                            'oldChecked'    => $entry->stop_processing,
                            'oldProhibited' => str_starts_with((string) $entry->trigger_type, '-'),
                            'count'         => $count,
                            'triggers'      => $triggers,
                        ]
                    )->render();
                } catch (\Throwable $e) {
                    app('log')->debug(sprintf('Throwable was thrown in getCurrentTriggers(): %s', $e->getMessage()));
                    app('log')->error($e->getTraceAsString());

                    throw new FireflyException(sprintf('Could not render: %s', $e->getMessage()), 0, $e);
                }

                ++$index;
            }
        }

        return $renderedEntries;
    }

    /**
     * Returns all the incomes that went to the given asset account.
     *
     * @throws FireflyException
     */
    protected function incomeEntry(array $attributes): string // generate view for report.
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var PopupReportInterface $popupHelper */
        $popupHelper       = app(PopupReportInterface::class);
        $account           = $accountRepository->find((int) $attributes['accountId']);

        if (null === $account) {
            return 'This is an unknown category. Apologies.';
        }

        $journals          = $popupHelper->byIncome($account, $attributes);

        try {
            $view = view('popup.report.income-entry', compact('journals', 'account'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Could not render: %s', $e->getMessage()));
            $view = 'Firefly III could not render the view. Please see the log files.';

            throw new FireflyException($view, 0, $e);
        }

        return $view;
    }

    /**
     * Get options for default report.
     *
     * @throws FireflyException
     */
    protected function noReportOptions(): string // render a view
    {
        try {
            $result = view('reports.options.no-options')->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.options.no-options: %s', $e->getMessage()));
            $result = 'Could not render view.';

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }

    /**
     * Get options for tag report.
     *
     * @throws FireflyException
     */
    protected function tagReportOptions(): string // render a view
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $tags       = $repository->get();

        try {
            $result = view('reports.options.tag', compact('tags'))->render();
        } catch (\Throwable $e) {
            app('log')->error(sprintf('Cannot render reports.options.tag: %s', $e->getMessage()));
            $result = 'Could not render view.';

            throw new FireflyException($result, 0, $e);
        }

        return $result;
    }
}
