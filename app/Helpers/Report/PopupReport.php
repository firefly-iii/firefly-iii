<?php

/**
 * PopupReport.php
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

namespace FireflyIII\Helpers\Report;

use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class PopupReport.
 */
class PopupReport implements PopupReportInterface
{
    /**
     * Collect the transactions for one account and one budget.
     */
    public function balanceForBudget(Budget $budget, Account $account, array $attributes): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))
            ->withAccountInformation()
            ->withBudgetInformation()
            ->withCategoryInformation()
            ->setRange($attributes['startDate'], $attributes['endDate'])->setBudget($budget)
        ;

        return $collector->getExtractedJournals();
    }

    /**
     * Collect the transactions for one account and no budget.
     */
    public function balanceForNoBudget(Account $account, array $attributes): array
    {
        // filter by currency, if set.
        $currencyId = $attributes['currencyId'] ?? null;
        $currency   = null;
        if (null !== $currencyId) {
            /** @var CurrencyRepositoryInterface $repos */
            $repos    = app(CurrencyRepositoryInterface::class);
            $currency = $repos->find((int) $currencyId);
        }

        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);
        $collector
            ->setAccounts(new Collection([$account]))
            ->setTypes([TransactionType::WITHDRAWAL])
            ->withAccountInformation()
            ->withCategoryInformation()
            ->setRange($attributes['startDate'], $attributes['endDate'])
            ->withoutBudget()
        ;

        if (null !== $currency) {
            $collector->setCurrency($currency);
        }

        return $collector->getExtractedJournals();
    }

    /**
     * Collect the transactions for a budget.
     */
    public function byBudget(Budget $budget, array $attributes): array
    {
        // filter by currency, if set.
        $currencyId = $attributes['currencyId'] ?? null;
        $currency   = null;
        if (null !== $currencyId) {
            /** @var CurrencyRepositoryInterface $repos */
            $repos    = app(CurrencyRepositoryInterface::class);
            $currency = $repos->find((int) $currencyId);
        }

        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);
        $collector
            ->setAccounts($attributes['accounts'])
            ->withAccountInformation()
            ->withBudgetInformation()
            ->withCategoryInformation()
            ->setRange($attributes['startDate'], $attributes['endDate'])
        ;

        if (null !== $currency) {
            $collector->setCurrency($currency);
        }
        if (null === $budget->id || 0 === $budget->id) {
            $collector->setTypes([TransactionType::WITHDRAWAL])->withoutBudget();
        }
        if (null !== $budget->id && 0 !== $budget->id) {
            $collector->setBudget($budget);
        }

        return $collector->getExtractedJournals();
    }

    /**
     * Collect journals by a category.
     */
    public function byCategory(?Category $category, array $attributes): array
    {
        // filter by currency, if set.
        $currencyId = $attributes['currencyId'] ?? null;
        $currency   = null;
        if (null !== $currencyId) {
            /** @var CurrencyRepositoryInterface $repos */
            $repos    = app(CurrencyRepositoryInterface::class);
            $currency = $repos->find((int) $currencyId);
        }

        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);

        $collector->setAccounts($attributes['accounts'])
            ->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::DEPOSIT])
            ->withAccountInformation()
            ->withBudgetInformation()
            ->withCategoryInformation()
            ->setRange($attributes['startDate'], $attributes['endDate'])->withAccountInformation()
        ;

        if (null !== $category) {
            $collector->setCategory($category);
        }
        if (null === $category) {
            $collector->withoutCategory();
        }

        if (null !== $currency) {
            $collector->setCurrency($currency);
        }

        return $collector->getExtractedJournals();
    }

    /**
     * Group transactions by expense.
     */
    public function byExpenses(Account $account, array $attributes): array
    {
        // filter by currency, if set.
        $currencyId        = $attributes['currencyId'] ?? null;
        $currency          = null;
        if (null !== $currencyId) {
            /** @var CurrencyRepositoryInterface $repos */
            $repos    = app(CurrencyRepositoryInterface::class);
            $currency = $repos->find((int) $currencyId);
        }

        /** @var JournalRepositoryInterface $repository */
        $repository        = app(JournalRepositoryInterface::class);
        $repository->setUser($account->user);

        $accountRepository = app(AccountRepositoryInterface::class);
        $accountRepository->setUser($account->user);

        /** @var GroupCollectorInterface $collector */
        $collector         = app(GroupCollectorInterface::class);

        // set report accounts + the request accounts:
        // $set = $attributes['accounts'] ?? new Collection;
        // $set->push($account);

        $collector->setDestinationAccounts(new Collection([$account]))
            ->setRange($attributes['startDate'], $attributes['endDate'])
            ->withAccountInformation()
            ->withBudgetInformation()
            ->withCategoryInformation()
            ->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
        ;

        if (null !== $currency) {
            $collector->setCurrency($currency);
        }

        return $collector->getExtractedJournals();
    }

    /**
     * Collect transactions by income.
     */
    public function byIncome(Account $account, array $attributes): array
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $repository->setUser($account->user);

        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);
        $collector
            ->setSourceAccounts(new Collection([$account]))
            ->setDestinationAccounts($attributes['accounts'])
            ->setRange($attributes['startDate'], $attributes['endDate'])
            ->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
            ->withAccountInformation()
            ->withBudgetInformation()
            ->withCategoryInformation()
            ->withAccountInformation()
        ;

        return $collector->getExtractedJournals();
    }
}
