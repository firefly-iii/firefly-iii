<?php
/**
 * PeriodOverview.php
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
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\Account;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Trait PeriodOverview.
 *
 * - Group expenses, income, etc. under this period.
 * - Returns collection of arrays. Possible fields are:
 * -    start (string),
 *      end (string),
 *      title (string),
 *      spent (string),
 *      earned (string),
 *      transferred (string)
 *
 *
 */
trait PeriodOverview
{
    /**
     * This method returns "period entries", so nov-2015, dec-2015, etc etc (this depends on the users session range)
     * and for each period, the amount of money spent and earned. This is a complex operation which is cached for
     * performance reasons.
     *
     * @param Account $account the account involved
     * @param Carbon  $date
     *
     * @return Collection
     */
    protected function getAccountPeriodOverview(Account $account, Carbon $date): Collection // period overview
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $range      = app('preferences')->get('viewRange', '1M')->data;
        $end        = $repository->oldestJournalDate($account) ?? Carbon::now()->subMonth()->startOfMonth();
        $start      = clone $date;

        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('account-show-period-entries');
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;
        // loop dates
        foreach ($dates as $currentDate) {
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAccounts(new Collection([$account]))->setRange($currentDate['start'], $currentDate['end'])->setTypes([TransactionType::DEPOSIT])
                      ->withOpposingAccount();
            $set    = $collector->getTransactions();
            $earned = $this->groupByCurrency($set);

            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAccounts(new Collection([$account]))->setRange($currentDate['start'], $currentDate['end'])->setTypes([TransactionType::WITHDRAWAL])
                      ->withOpposingAccount();
            $set   = $collector->getTransactions();
            $spent = $this->groupByCurrency($set);

            $title = app('navigation')->periodShow($currentDate['start'], $currentDate['period']);
            /** @noinspection PhpUndefinedMethodInspection */
            $entries->push(
                [
                    'transactions' => 0,
                    'title'        => $title,
                    'spent'        => $spent,
                    'earned'       => $earned,
                    'transferred'  => '0',
                    'route'        => route('accounts.show', [$account->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),

                ]
            );
        }

        $cache->store($entries);

        return $entries;
    }

    /**
     * Gets period overview used for budgets.
     *
     * @return Collection
     */
    protected function getBudgetPeriodOverview(Carbon $date): Collection
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $first      = $repository->firstNull();
        $end        = null === $first ? new Carbon : $first->date;
        $start      = clone $date;
        $range      = app('preferences')->get('viewRange', '1M')->data;
        $entries    = new Collection;
        $cache      = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('no-budget-period-entries');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var array $dates */
        $dates = app('navigation')->blockPeriods($start, $end, $range);
        foreach ($dates as $currentDate) {
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($currentDate['start'], $currentDate['end'])->withoutBudget()->withOpposingAccount()->setTypes(
                [TransactionType::WITHDRAWAL]
            );
            $set   = $collector->getTransactions();
            $count = $set->count();
            $spent = $this->groupByCurrency($set);
            $title = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries->push(
                [
                    'route'        => route('budgets.no-budget', [$currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                    'transactions' => $count,
                    'title'        => $title,
                    'spent'        => $spent,
                    'earned'       => '0',
                    'transferred'  => '0',
                ]
            );
        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * Get a period overview for category.
     *
     * TODO refactor me.
     *
     * @param Category $category
     * @param Carbon   $date
     *
     * @return Collection
     */
    protected function getCategoryPeriodOverview(Category $category, Carbon $date): Collection // periodOverview method
    {
        /** @var JournalRepositoryInterface $journalRepository */
        $journalRepository = app(JournalRepositoryInterface::class);
        /** @var CategoryRepositoryInterface $categoryRepository */
        $categoryRepository = app(CategoryRepositoryInterface::class);


        $range = app('preferences')->get('viewRange', '1M')->data;
        $first = $journalRepository->firstNull();
        $end   = null === $first ? new Carbon : $first->date;
        $start = clone $date;

        // properties for entries with their amounts.
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($range);
        $cache->addProperty('categories.entries');
        $cache->addProperty($category->id);

        if ($cache->has()) {
            //return $cache->get(); // @codeCoverageIgnore
        }
        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;

        foreach ($dates as $currentDate) {
            $spent  = $categoryRepository->spentInPeriodCollection(new Collection([$category]), new Collection, $currentDate['start'], $currentDate['end']);
            $earned = $categoryRepository->earnedInPeriodCollection(new Collection([$category]), new Collection, $currentDate['start'], $currentDate['end']);
            $spent  = $this->groupByCurrency($spent);
            $earned = $this->groupByCurrency($earned);
            // amount transferred
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($currentDate['start'], $currentDate['end'])->setCategory($category)
                      ->withOpposingAccount()->setTypes([TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $transferred = $this->groupByCurrency($collector->getTransactions());
            $title       = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries->push(
                [
                    'route'       => route('categories.show', [$category->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                    'title'       => $title,
                    'spent'       => $spent,
                    'earned'      => $earned,
                    'transferred' => $transferred,
                ]
            );
        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * Get overview of periods for tag.
     *
     * TODO refactor this.
     *
     * @param Tag $tag
     *
     * @return Collection
     */
    protected function getTagPeriodOverview(Tag $tag): Collection // period overview for tags.
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        // get first and last tag date from tag:
        $range = app('preferences')->get('viewRange', '1M')->data;
        /** @var Carbon $end */
        $end   = app('navigation')->endOfX($repository->lastUseDate($tag) ?? new Carbon, $range, null);
        $start = $repository->firstUseDate($tag) ?? new Carbon;


        // properties for entries with their amounts.
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('tag.entries');
        $cache->addProperty($tag->id);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $collection = new Collection;
        $currentEnd = clone $end;
        // while end larger or equal to start
        while ($currentEnd >= $start) {
            $currentStart = app('navigation')->startOfPeriod($currentEnd, $range);

            // get expenses and what-not in this period and this tag.
            $arr = [
                'string' => $end->format('Y-m-d'),
                'name'   => app('navigation')->periodShow($currentEnd, $range),
                'start'  => clone $currentStart,
                'end'    => clone $currentEnd,
                'date'   => clone $end,
                'spent'  => $repository->spentInPeriod($tag, $currentStart, $currentEnd),
                'earned' => $repository->earnedInPeriod($tag, $currentStart, $currentEnd),
            ];
            $collection->push($arr);

            /** @var Carbon $currentEnd */
            $currentEnd = clone $currentStart;
            $currentEnd->subDay();
        }
        $cache->store($collection);

        return $collection;
    }

    /**
     * Get period overview for index.
     *
     * TODO refactor me.
     *
     * @param string $what
     * @param Carbon $date
     *
     * @return Collection
     */
    protected function getTransactionPeriodOverview(string $what, Carbon $date): Collection // period overview for transactions.
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $range      = app('preferences')->get('viewRange', '1M')->data;
        $first      = $repository->firstNull();
        $start      = Carbon::now()->subYear();
        $types      = config('firefly.transactionTypesByWhat.' . $what);
        $entries    = new Collection;
        if (null !== $first) {
            $start = $first->date;
        }
        if ($date < $start) {
            [$start, $date] = [$date, $start]; // @codeCoverageIgnore
        }

        /** @var array $dates */
        $dates = app('navigation')->blockPeriods($start, $date, $range);

        foreach ($dates as $currentDate) {
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($currentDate['start'], $currentDate['end'])->withOpposingAccount()->setTypes($types);
            $collector->removeFilter(InternalTransferFilter::class);
            $transactions = $collector->getTransactions();

            if ($transactions->count() > 0) {
                $sums     = $this->sumPerCurrency($transactions);
                $dateName = app('navigation')->periodShow($currentDate['start'], $currentDate['period']);
                $sum      = $transactions->sum('transaction_amount');
                /** @noinspection PhpUndefinedMethodInspection */
                $entries->push(
                    [
                        'name'  => $dateName,
                        'sums'  => $sums,
                        'sum'   => $sum,
                        'start' => $currentDate['start']->format('Y-m-d'),
                        'end'   => $currentDate['end']->format('Y-m-d'),
                    ]
                );
            }
        }

        return $entries;
    }

    /**
     * Collect the sum per currency.
     *
     * @param Collection $collection
     *
     * @return array
     */
    protected function sumPerCurrency(Collection $collection): array // helper for transactions (math, calculations)
    {
        $return = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $currencyId = (int)$transaction->transaction_currency_id;

            // save currency information:
            if (!isset($return[$currencyId])) {
                $currencySymbol      = $transaction->transaction_currency_symbol;
                $decimalPlaces       = $transaction->transaction_currency_dp;
                $currencyCode        = $transaction->transaction_currency_code;
                $return[$currencyId] = [
                    'currency' => [
                        'id'     => $currencyId,
                        'code'   => $currencyCode,
                        'symbol' => $currencySymbol,
                        'dp'     => $decimalPlaces,
                    ],
                    'sum'      => '0',
                    'count'    => 0,
                ];
            }
            // save amount:
            $return[$currencyId]['sum'] = bcadd($return[$currencyId]['sum'], $transaction->transaction_amount);
            ++$return[$currencyId]['count'];
        }
        asort($return);

        return $return;
    }

    /**
     * @param Collection $transactions
     *
     * @return array
     */
    private function groupByCurrency(Collection $transactions): array
    {
        $return = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $currencyId = (int)$transaction->transaction_currency_id;
            if (!isset($return[$currencyId])) {
                $currency                 = new TransactionCurrency;
                $currency->symbol         = $transaction->transaction_currency_symbol;
                $currency->decimal_places = $transaction->transaction_currency_dp;
                $currency->name           = $transaction->transaction_currency_name;
                $return[$currencyId]      = [
                    'amount'   => '0',
                    'currency' => $currency,
                ];
            }
            $return[$currencyId]['amount'] = bcadd($return[$currencyId]['amount'], $transaction->transaction_amount);
        }

        return $return;
    }

}