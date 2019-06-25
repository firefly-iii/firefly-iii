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
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
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
 * - Always request start date and end date.
 * - Group expenses, income, etc. under this period.
 * - Returns collection of arrays. Fields
 *      title (string),
 *      route (string)
 *      total_transactions (int)
 *      spent (array),
 *      earned (array),
 *      transferred_away (array)
 *      transferred_in (array)
 *      transferred (array)
 *
 * each array has the following format:
 * currency_id => [
 *       currency_id : 1, (int)
 *       currency_symbol : X (str)
 *       currency_name: Euro (str)
 *       currency_code: EUR (str)
 *       amount: -1234 (str)
 *       count: 23
 *       ]
 *
 */
trait PeriodOverview
{

    /**
     * This method returns "period entries", so nov-2015, dec-2015, etc etc (this depends on the users session range)
     * and for each period, the amount of money spent and earned. This is a complex operation which is cached for
     * performance reasons.
     *
     * @param Account $account The account involved
     * @param Carbon $date The start date.
     * @param Carbon $end The end date.
     *
     * @return array
     */
    protected function getAccountPeriodOverview(Account $account, Carbon $start, Carbon $end): array
    {
        $range = app('preferences')->get('viewRange', '1M')->data;

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
        $entries = [];

        // collect all expenses in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]));
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::DEPOSIT]);
        $earnedSet = $collector->getExtractedJournals();

        // collect all income in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]));
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL]);
        $spentSet = $collector->getExtractedJournals();

        // collect all transfers in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]));
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::TRANSFER]);
        $transferSet = $collector->getExtractedJournals();

        // loop dates
        foreach ($dates as $currentDate) {
            $title           = app('navigation')->periodShow($currentDate['start'], $currentDate['period']);
            $earned          = $this->filterJournalsByDate($earnedSet, $currentDate['start'], $currentDate['end']);
            $spent           = $this->filterJournalsByDate($spentSet, $currentDate['start'], $currentDate['end']);
            $transferredAway = $this->filterTransferredAway($account, $this->filterJournalsByDate($transferSet, $currentDate['start'], $currentDate['end']));
            $transferredIn   = $this->filterTransferredIn($account, $this->filterJournalsByDate($transferSet, $currentDate['start'], $currentDate['end']));
            $entries[]       =
                [
                    'title' => $title,
                    'route' =>
                        route('accounts.show', [$account->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),

                    'total_transactions' => count($spent) + count($earned) + count($transferredAway) + count($transferredIn),
                    'spent'              => $this->groupByCurrency($spent),
                    'earned'             => $this->groupByCurrency($earned),
                    'transferred_away'   => $this->groupByCurrency($transferredAway),
                    'transferred_in'     => $this->groupByCurrency($transferredIn),
                ];
        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * Overview for single category. Has been refactored recently.
     *
     * @param Category $category
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    protected function getCategoryPeriodOverview(Category $category, Carbon $start, Carbon $end): array
    {
        $range = app('preferences')->get('viewRange', '1M')->data;

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
            //return $cache->get(); // @codeCoverageIgnore
        }
        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = [];

        // collect all expenses in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setCategory($category);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::DEPOSIT]);
        $earnedSet = $collector->getExtractedJournals();

        // collect all income in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setCategory($category);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL]);
        $spentSet = $collector->getExtractedJournals();

        // collect all transfers in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setCategory($category);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::TRANSFER]);
        $transferSet = $collector->getExtractedJournals();


        foreach ($dates as $currentDate) {
            $spent       = $this->filterJournalsByDate($spentSet, $currentDate['start'], $currentDate['end']);
            $earned      = $this->filterJournalsByDate($earnedSet, $currentDate['start'], $currentDate['end']);
            $transferred = $this->filterJournalsByDate($transferSet, $currentDate['start'], $currentDate['end']);
            $title       = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries[]   =
                [
                    'transactions'       => 0,
                    'title'              => $title,
                    'route'              => route('categories.show',
                                                  [$category->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                    'total_transactions' => count($spent) + count($earned) + count($transferred),
                    'spent'              => $this->groupByCurrency($spent),
                    'earned'             => $this->groupByCurrency($earned),
                    'transferred'        => $this->groupByCurrency($transferred),
                ];
        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * Same as above, but for lists that involve transactions without a budget.
     *
     * This method has been refactored recently.
     *
     * @param Carbon $start
     * @param Carbon $date
     *
     * @return array
     */
    protected function getNoBudgetPeriodOverview(Carbon $start, Carbon $end): array
    {
        $range = app('preferences')->get('viewRange', '1M')->data;

        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('no-budget-period-entries');

        if ($cache->has()) {
            //return $cache->get(); // @codeCoverageIgnore
        }

        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = [];


        // get all expenses without a budget.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withoutBudget()->withAccountInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $journals = $collector->getExtractedJournals();

        foreach ($dates as $currentDate) {
            $set       = $this->filterJournalsByDate($journals, $currentDate['start'], $currentDate['end']);
            $title     = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries[] =
                [
                    'title'              => $title,
                    'route'              => route('budgets.no-budget', [$currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                    'total_transactions' => count($set),
                    'spent'              => $this->groupByCurrency($set),
                    'earned'             => [],
                    'transferred_away'   => [],
                    'transferred_in'     => [],
                ];
        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * Show period overview for no category view.
     *
     * @param Carbon $theDate
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getNoCategoryPeriodOverview(Carbon $theDate): array
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
            //return $cache->get(); // @codeCoverageIgnore
        }

        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = [];

        // collect all expenses in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->withoutCategory();
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::DEPOSIT]);
        $earnedSet = $collector->getExtractedJournals();

        // collect all income in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->withoutCategory();
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL]);
        $spentSet = $collector->getExtractedJournals();

        // collect all transfers in this period:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->withoutCategory();
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionType::TRANSFER]);
        $transferSet = $collector->getExtractedJournals();

        /** @var array $currentDate */
        foreach ($dates as $currentDate) {
            $spent       = $this->filterJournalsByDate($spentSet, $currentDate['start'], $currentDate['end']);
            $earned      = $this->filterJournalsByDate($earnedSet, $currentDate['start'], $currentDate['end']);
            $transferred = $this->filterJournalsByDate($transferSet, $currentDate['start'], $currentDate['end']);
            $title       = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries[]   =
                [
                    'title'              => $title,
                    'route'              => route('categories.no-category', [$currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                    'total_transactions' => count($spent) + count($earned) + count($transferred),
                    'spent'              => $this->groupByCurrency($spent),
                    'earned'             => $this->groupByCurrency($earned),
                    'transferred'        => $this->groupByCurrency($transferred),
                ];
        }
        Log::debug('End of loops');
        $cache->store($entries);

        return $entries;
    }

    /**
     * This shows a period overview for a tag. It goes back in time and lists all relevant transactions and sums.
     *
     * @param Tag $tag
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    protected function getTagPeriodOverview(Tag $tag, Carbon $date): Collection // period overview for tags.
    {
        die('not yet complete');
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
                    'transactions' => count($spentSet) + count($earnedSet) + count($transferredSet),
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
     * @param string $transactionType
     * @param Carbon $endDate
     *
     * @return Collection
     */
    protected function getTransactionPeriodOverview(string $transactionType, Carbon $endDate): Collection
    {
        die('not yet complete');
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $range      = app('preferences')->get('viewRange', '1M')->data;
        $endJournal = $repository->firstNull();
        $end        = null === $endJournal ? new Carbon : $endJournal->date;
        $start      = clone $endDate;
        $types      = config('firefly.transactionTypesByType.' . $transactionType);

        if ($end < $start) {
            [$start, $end] = [$end, $start]; // @codeCoverageIgnore
        }

        /** @var array $dates */
        $dates   = app('navigation')->blockPeriods($start, $end, $range);
        $entries = new Collection;

        foreach ($dates as $currentDate) {
            /** @var GroupCollectorInterface $collector */
            $collector = app(GroupCollectorInterface::class);
            $collector->setTypes($types)->setRange($currentDate['start'], $currentDate['end']);
            $journals = $collector->getExtractedJournals();
            $amounts  = $this->getJournalsSum($journals);

            $spent       = [];
            $earned      = [];
            $transferred = [];

            // set to correct array
            if ('expenses' === $transactionType || 'withdrawal' === $transactionType) {
                $spent = $amounts;
            }
            if ('revenue' === $transactionType || 'deposit' === $transactionType) {
                $earned = $amounts;
            }
            if ('transfer' === $transactionType || 'transfers' === $transactionType) {
                $transferred = $amounts;
            }


            $title = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries->push(
                [
                    'transactions' => $amounts['count'],
                    'title'        => $title,
                    'spent'        => $spent,
                    'earned'       => $earned,
                    'transferred'  => $transferred,
                    'route'        => route(
                        'transactions.index', [$transactionType, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]
                    ),
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
     * Return only transactions where $account is the source.
     * @param Account $account
     * @param array $journals
     * @return array
     */
    private function filterTransferredAway(Account $account, array $journals): array
    {
        $return = [];
        /** @var array $journal */
        foreach ($journals as $journal) {
            if ($account->id === (int)$journal['source_account_id']) {
                $return[] = $journal;
            }
        }

        return $return;
    }

    /**
     * Return only transactions where $account is the source.
     * @param Account $account
     * @param array $journals
     * @return array
     */
    private function filterTransferredIn(Account $account, array $journals): array
    {
        $return = [];
        /** @var array $journal */
        foreach ($journals as $journal) {
            if ($account->id === (int)$journal['destination_account_id']) {
                $return[] = $journal;
            }
        }

        return $return;
    }

    /**
     * Filter a list of journals by a set of dates, and then group them by currency.
     *
     * @param array $array
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    private function filterJournalsByDate(array $array, Carbon $start, Carbon $end): array
    {
        $result = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            if ($journal['date'] <= $end && $journal['date'] >= $start) {
                $result[] = $journal;
            }
        }

        return $result;
    }

    /**
     * @param array $journals
     *
     * @return array
     */
    private function groupByCurrency(array $journals): array
    {
        $return = [];
        /** @var array $journal */
        foreach ($journals as $journal) {
            $currencyId        = (int)$journal['currency_id'];
            $foreignCurrencyId = $journal['foreign_currency_id'];
            if (!isset($return[$currencyId])) {
                $return[$currencyId] = [
                    'amount'                  => '0',
                    'count'                   => 0,
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $return[$currencyId]['amount'] = bcadd($return[$currencyId]['amount'], $journal['amount']);
            $return[$currencyId]['count']++;


            if (null !== $foreignCurrencyId) {
                if (!isset($return[$foreignCurrencyId])) {
                    $return[$foreignCurrencyId] = [
                        'amount'                  => '0',
                        'count'                   => 0,
                        'currency_id'             => (int)$foreignCurrencyId,
                        'currency_name'           => $journal['foreign_currency_name'],
                        'currency_code'           => $journal['foreign_currency_code'],
                        'currency_symbol'         => $journal['foreign_currency_symbol'],
                        'currency_decimal_places' => $journal['foreign_currency_decimal_places'],
                    ];

                }
                $return[$foreignCurrencyId]['count']++;
                $return[$foreignCurrencyId]['amount'] = bcadd($return[$foreignCurrencyId]['amount'], $journal['foreign_amount']);
            }

        }

        return $return;
    }

    /**
     * @param array $journals
     * @return array
     */
    private function getJournalsSum(array $journals): array
    {
        $return = [
            'count' => 0,
            'sums'  => [],
        ];
        if (0 === count($journals)) {
            return $return;
        }

        foreach ($journals as $row) {
            $return['count']++;
            $currencyId = (int)$row['currency_id'];
            if (!isset($return['sums'][$currencyId])) {
                $return['sums'][$currencyId] = [
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_code'           => $row['currency_code'],
                    'currency_symbol'         => $row['currency_symbol'],
                    'currency_name'           => $row['currency_name'],
                    'currency_decimal_places' => (int)$row['currency_decimal_places'],
                ];
            }
            // add amounts:
            $return['sums'][$currencyId]['sum'] = bcadd($return['sums'][$currencyId]['sum'], (string)$row['amount']);

            // same but for foreign amounts:
            if (null !== $row['foreign_currency_id'] && 0 !== $row['foreign_currency_id']) {
                $foreignCurrencyId                         = (int)$row['foreign_currency_id'];
                $return['sums'][$foreignCurrencyId]        = [
                    'sum'                     => '0',
                    'currency_id'             => $foreignCurrencyId,
                    'currency_code'           => $row['foreign_currency_code'],
                    'currency_symbol'         => $row['foreign_currency_symbol'],
                    'currency_name'           => $row['foreign_currency_name'],
                    'currency_decimal_places' => (int)$row['foreign_currency_decimal_places'],
                ];
                $return['sums'][$foreignCurrencyId]['sum'] = bcadd($return['sums'][$foreignCurrencyId]['sum'], (string)$row['foreign_amount']);
            }
        }

        return $return;
    }

}
