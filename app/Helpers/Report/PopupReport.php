<?php
/**
 * PopupReport.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Report;

use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Illuminate\Support\Collection;

/**
 * Class PopupReport
 *
 * @package FireflyIII\Helpers\Report
 */
class PopupReport implements PopupReportInterface
{


    /**
     * @param $account
     * @param $attributes
     *
     * @return Collection
     */
    public function balanceDifference($account, $attributes): Collection
    {
        // row that displays difference
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector
            ->setAccounts(new Collection([$account]))
            ->setTypes([TransactionType::WITHDRAWAL])
            ->setRange($attributes['startDate'], $attributes['endDate'])
            ->withoutBudget();
        $journals = $collector->getJournals();


        return $journals->filter(
            function (Transaction $transaction) {
                $tags = $transaction->transactionJournal->tags()->where('tagMode', 'balancingAct')->count();
                if ($tags === 0) {
                    return true;
                }

                return false;
            }
        );
    }

    /**
     * @param Budget  $budget
     * @param Account $account
     * @param array   $attributes
     *
     * @return Collection
     */
    public function balanceForBudget(Budget $budget, Account $account, array $attributes): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($attributes['startDate'], $attributes['endDate'])->setBudget($budget);
        $journals = $collector->getJournals();

        return $journals;
    }

    /**
     * @param Account $account
     * @param array   $attributes
     *
     * @return Collection
     */
    public function balanceForNoBudget(Account $account, array $attributes): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector
            ->setAccounts(new Collection([$account]))
            ->setTypes([TransactionType::WITHDRAWAL])
            ->setRange($attributes['startDate'], $attributes['endDate'])
            ->withoutBudget();

        return $collector->getJournals();
    }

    /**
     * @param Budget $budget
     * @param array  $attributes
     *
     * @return Collection
     */
    public function byBudget(Budget $budget, array $attributes): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);

        $collector->setAccounts($attributes['accounts'])->setRange($attributes['startDate'], $attributes['endDate']);

        if (is_null($budget->id)) {
            $collector->setTypes([TransactionType::WITHDRAWAL])->withoutBudget();
        }
        if (!is_null($budget->id)) {
            $collector->setBudget($budget);
        }
        $journals = $collector->getJournals();

        return $journals;
    }

    /**
     * @param Category $category
     * @param array    $attributes
     *
     * @return Collection
     */
    public function byCategory(Category $category, array $attributes): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($attributes['accounts'])->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->setRange($attributes['startDate'], $attributes['endDate'])
                  ->setCategory($category);
        $journals = $collector->getJournals();

        return $journals;
    }

    /**
     * @param Account $account
     * @param array   $attributes
     *
     * @return Collection
     */
    public function byExpenses(Account $account, array $attributes): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);

        $collector->setAccounts(new Collection([$account]))->setRange($attributes['startDate'], $attributes['endDate'])
                  ->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER]);
        $journals = $collector->getJournals();

        $report = $attributes['accounts']->pluck('id')->toArray(); // accounts used in this report

        // filter for transfers and withdrawals TO the given $account
        $journals = $journals->filter(
            function (Transaction $transaction) use ($report) {
                // get the destinations:
                $sources = $transaction->transactionJournal->sourceAccountList()->pluck('id')->toArray();

                // do these intersect with the current list?
                return !empty(array_intersect($report, $sources));
            }
        );

        return $journals;
    }

    /**
     * @param Account $account
     * @param array   $attributes
     *
     * @return Collection
     */
    public function byIncome(Account $account, array $attributes): Collection
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($attributes['startDate'], $attributes['endDate'])
                  ->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER]);
        $journals = $collector->getJournals();
        $report   = $attributes['accounts']->pluck('id')->toArray(); // accounts used in this report

        // filter the set so the destinations outside of $attributes['accounts'] are not included.
        $journals = $journals->filter(
            function (Transaction $transaction) use ($report) {
                // get the destinations:
                $destinations = $transaction->transactionJournal->destinationAccountList()->pluck('id')->toArray();

                // do these intersect with the current list?
                return !empty(array_intersect($report, $destinations));
            }
        );

        return $journals;
    }
}
