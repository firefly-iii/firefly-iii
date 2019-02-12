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
use Log;

/**
 * Trait PeriodOverview.
 *
 * TODO verify this all works as expected.
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
     * The method has been refactored recently for better performance.
     *
     * @param Account $account The account involved
     * @param Carbon  $date    The start date.
     *
     * @return Collection
     */
    protected function getAccountPeriodOverview(Account $account, Carbon $date): Collection
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
            $earnedSet = $collector->getTransactions();
            $earned    = $this->groupByCurrency($earnedSet);

            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAccounts(new Collection([$account]))->setRange($currentDate['start'], $currentDate['end'])->setTypes([TransactionType::WITHDRAWAL])
                      ->withOpposingAccount();
            $spentSet = $collector->getTransactions();
            $spent    = $this->groupByCurrency($spentSet);

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
     * Overview for single category. Has been refactored recently.
     *
     * @param Category $category
     * @param Carbon   $date
     *
     * @return Collection
     */
    protected function getCategoryPeriodOverview(Category $category, Carbon $date): Collection
    {
        /** @var JournalRepositoryInterface $journalRepository */
        $journalRepository = app(JournalRepositoryInterface::class);
        $range             = app('preferences')->get('viewRange', '1M')->data;
        $first             = $journalRepository->firstNull();
        $end               = null === $first ? new Carbon : $first->date;
        $start             = clone $date;

        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        // properties for entries with their amounts.
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($range);
        $cache->addProperty('category-show-period-entries');
        $cache->addProperty($category->id);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;
        /** @var CategoryRepositoryInterface $categoryRepository */
        $categoryRepository = app(CategoryRepositoryInterface::class);

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

            $title = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries->push(
                [
                    'transactions' => 0,
                    'title'        => $title,
                    'spent'        => $spent,
                    'earned'       => $earned,
                    'transferred'  => $transferred,
                    'route'        => route('categories.show', [$category->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                ]
            );
        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * Same as above, but for lists that involve transactions without a budget.
     *
     * This method has been refactored recently.
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    protected function getNoBudgetPeriodOverview(Carbon $date): Collection
    {
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $first      = $repository->firstNull();
        $end        = null === $first ? new Carbon : $first->date;
        $start      = clone $date;
        $range      = app('preferences')->get('viewRange', '1M')->data;

        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('no-budget-period-entries');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;
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
                    'transactions' => $count,
                    'title'        => $title,
                    'spent'        => $spent,
                    'earned'       => '0',
                    'transferred'  => '0',
                    'route'        => route('budgets.no-budget', [$currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                ]
            );
        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * TODO has to be synced with the others.
     *
     * Show period overview for no category view.
     *
     * @param Carbon $theDate
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getNoCategoryPeriodOverview(Carbon $theDate): Collection // period overview method.
    {
        Log::debug(sprintf('Now in getNoCategoryPeriodOverview(%s)', $theDate->format('Y-m-d')));
        $range = app('preferences')->get('viewRange', '1M')->data;
        $first = $this->journalRepos->firstNull();
        $start = null === $first ? new Carbon : $first->date;
        $end   = $theDate ?? new Carbon;

        Log::debug(sprintf('Start for getNoCategoryPeriodOverview() is %s', $start->format('Y-m-d')));
        Log::debug(sprintf('End for getNoCategoryPeriodOverview() is %s', $end->format('Y-m-d')));

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('no-category-period-entries');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;

        foreach ($dates as $date) {

            // count journals without category in this period:
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $count = $collector->getTransactions()->count();

            // amount transferred
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()
                      ->withOpposingAccount()->setTypes([TransactionType::TRANSFER]);
            $collector->removeFilter(InternalTransferFilter::class);
            $transferred = app('steam')->positive((string)$collector->getTransactions()->sum('transaction_amount'));

            // amount spent
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()->withOpposingAccount()->setTypes(
                [TransactionType::WITHDRAWAL]
            );
            $spent = $collector->getTransactions()->sum('transaction_amount');

            // amount earned
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($date['start'], $date['end'])->withoutCategory()->withOpposingAccount()->setTypes(
                [TransactionType::DEPOSIT]
            );
            $earned = $collector->getTransactions()->sum('transaction_amount');
            /** @noinspection PhpUndefinedMethodInspection */
            $dateStr  = $date['end']->format('Y-m-d');
            $dateName = app('navigation')->periodShow($date['end'], $date['period']);
            $entries->push(
                [
                    'string'      => $dateStr,
                    'name'        => $dateName,
                    'count'       => $count,
                    'spent'       => $spent,
                    'earned'      => $earned,
                    'transferred' => $transferred,
                    'start'       => clone $date['start'],
                    'end'         => clone $date['end'],
                ]
            );
        }
        Log::debug('End of loops');
        $cache->store($entries);

        return $entries;
    }

    /**
     * This shows a period overview for a tag. It goes back in time and lists all relevant transactions and sums.
     *
     * @param Tag    $tag
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    protected function getTagPeriodOverview(Tag $tag, Carbon $date): Collection // period overview for tags.
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $range      = app('preferences')->get('viewRange', '1M')->data;
        /** @var Carbon $end */
        $start = clone $date;
        $end   = $repository->firstUseDate($tag) ?? new Carbon;


        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        // properties for entries with their amounts.
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('tag-period-entries');
        $cache->addProperty($tag->id);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;
        // while end larger or equal to start
        foreach ($dates as $currentDate) {

            $spentSet       = $repository->expenseInPeriod($tag, $currentDate['start'], $currentDate['end']);
            $spent          = $this->groupByCurrency($spentSet);
            $earnedSet      = $repository->incomeInPeriod($tag, $currentDate['start'], $currentDate['end']);
            $earned         = $this->groupByCurrency($earnedSet);
            $transferredSet = $repository->transferredInPeriod($tag, $currentDate['start'], $currentDate['end']);
            $transferred    = $this->groupByCurrency($transferredSet);
            $title          = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);

            $entries->push(
                [
                    'transactions' => $spentSet->count() + $earnedSet->count() + $transferredSet->count(),
                    'title'        => $title,
                    'spent'        => $spent,
                    'earned'       => $earned,
                    'transferred'  => $transferred,
                    'route'        => route('tags.show', [$tag->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                ]
            );

        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * This list shows the overview of a type of transaction, for the period blocks on the list of transactions.
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
        $endJournal = $repository->firstNull();
        $end        = null === $endJournal ? new Carbon : $endJournal->date;
        $start      = clone $date;
        $types      = config('firefly.transactionTypesByWhat.' . $what);


        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        // properties for entries with their amounts.
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('transactions-period-entries');
        $cache->addProperty($what);


        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;

        foreach ($dates as $currentDate) {

            // get all expenses, income or transfers:
            /** @var TransactionCollectorInterface $collector */
            $collector = app(TransactionCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($currentDate['start'], $currentDate['end'])->withOpposingAccount()->setTypes($types);
            $collector->removeFilter(InternalTransferFilter::class);
            $transactions = $collector->getTransactions();
            $title        = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $grouped      = $this->groupByCurrency($transactions);
            $spent        = [];
            $earned       = [];
            $transferred  = [];
            if ('expenses' === $what || 'withdrawal' === $what) {
                $spent = $grouped;
            }
            if ('revenue' === $what || 'deposit' === $what) {
                $earned = $grouped;
            }
            if ('transfer' === $what || 'transfers' === $what) {
                $transferred = $grouped;
            }
            $entries->push(
                [
                    'transactions' => $transactions->count(),
                    'title'        => $title,
                    'spent'        => $spent,
                    'earned'       => $earned,
                    'transferred'  => $transferred,
                    'route'        => route('transactions.index', [$what, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                ]
            );
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
