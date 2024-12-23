<?php

/**
 * TransactionCalculation.php
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

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;

/**
 * Trait TransactionCalculation
 */
trait TransactionCalculation
{
    /**
     * Get all expenses for a set of accounts.
     */
    protected function getExpensesForOpposing(Collection $accounts, Collection $opposing, Carbon $start, Carbon $end): array
    {
        $total     = $accounts->merge($opposing);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($total)
            ->setRange($start, $end)
            ->withAccountInformation()
            ->setTypes([TransactionType::WITHDRAWAL])
        ;

        return $collector->getExtractedJournals();
    }

    /**
     * Get all expenses by tags.
     */
    protected function getExpensesForTags(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
            ->setTags($tags)->withAccountInformation()
        ;

        return $collector->getExtractedJournals();
    }

    /**
     * Helper function that collects expenses for the given budgets.
     */
    protected function getExpensesInBudgets(Collection $accounts, Collection $budgets, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
            ->setBudgets($budgets)->withAccountInformation()
        ;

        return $collector->getExtractedJournals();
    }

    /**
     * Get all expenses in a period for categories.
     */
    protected function getExpensesInCategories(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setAccounts($accounts)
            ->setRange($start, $end)
            ->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
            ->setCategories($categories)
            ->withAccountInformation()
        ;

        return $collector->getExtractedJournals();
    }

    /**
     * Get all income for a period and a bunch of categories.
     */
    protected function getIncomeForCategories(Collection $accounts, Collection $categories, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
            ->setCategories($categories)->withAccountInformation()
        ;

        return $collector->getExtractedJournals();
    }

    /**
     * Get the income for a set of accounts.
     */
    protected function getIncomeForOpposing(Collection $accounts, Collection $opposing, Carbon $start, Carbon $end): array
    {
        $total     = $accounts->merge($opposing);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($total)->setRange($start, $end)->withAccountInformation()->setTypes([TransactionType::DEPOSIT]);

        return $collector->getExtractedJournals();
    }

    /**
     * Get all income by tag.
     */
    protected function getIncomeForTags(Collection $accounts, Collection $tags, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
            ->setTags($tags)->withAccountInformation()
        ;

        return $collector->getExtractedJournals();
    }
}
