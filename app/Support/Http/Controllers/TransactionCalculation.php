<?php
/**
 * TransactionCalculation.php
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

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;

/**
 * Trait TransactionCalculation
 *
 */
trait TransactionCalculation
{
    /**
     * Get all expenses for a set of accounts.
     *
     * @param Collection $accounts
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    protected function getExpensesForOpposing(Collection $accounts, Collection $opposing, Carbon $start, Carbon $end): Collection // get data + augument
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setOpposingAccounts($opposing);

        return $collector->getTransactions();
    }

    /**
     * Get all expenses by tags.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     *
     */
    protected function getExpensesForTags(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): Collection // get data + augument
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setTags($tags)->withOpposingAccount();
        $collector->removeFilter(TransferFilter::class);

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(PositiveAmountFilter::class);

        return $collector->getTransactions();
    }

    /**
     * Helper function that collects expenses for the given budgets.
     *
     * @param Collection $accounts
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    protected function getExpensesInBudgets(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end): Collection // get data + augment with info
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setBudgets($budgets)->withOpposingAccount();
        $collector->removeFilter(TransferFilter::class);

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(PositiveAmountFilter::class);

        return $collector->getTransactions();
    }

    /**
     * Get all expenses in a period for categories.
     *
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     *
     *
     */
    protected function getExpensesInCategories(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): Collection // get data + augument
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setCategories($categories)->withOpposingAccount();
        $collector->removeFilter(TransferFilter::class);

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(PositiveAmountFilter::class);

        return $collector->getTransactions();
    }

    /**
     * Get all income for a period and a bunch of categories.
     *
     * @param Collection $accounts
     * @param Collection $categories
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    protected function getIncomeForCategories(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): Collection // get data + augument
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setCategories($categories)->withOpposingAccount();

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(NegativeAmountFilter::class);

        return $collector->getTransactions();
    }

    /**
     * Get the income for a set of accounts.
     *
     * @param Collection $accounts
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    protected function getIncomeForOpposing(Collection $accounts, Collection $opposing, Carbon $start, Carbon $end): Collection // get data + augument
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setOpposingAccounts($opposing);

        return $collector->getTransactions();
    }

    /**
     * Get all income by tag.
     *
     * @param Collection $accounts
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function getIncomeForTags(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): Collection // get data + augument
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->setTags($tags)->withOpposingAccount();

        $collector->addFilter(OpposingAccountFilter::class);
        $collector->addFilter(NegativeAmountFilter::class);

        return $collector->getTransactions();
    }

}