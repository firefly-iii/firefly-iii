<?php

/**
 * PeriodOverview.php
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
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Debug\Timer;

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
 */
trait PeriodOverview
{
    protected AccountRepositoryInterface $accountRepository;
    protected JournalRepositoryInterface $journalRepos;

    /**
     * This method returns "period entries", so nov-2015, dec-2015, etc etc (this depends on the users session range)
     * and for each period, the amount of money spent and earned. This is a complex operation which is cached for
     * performance reasons.
     *
     * @throws FireflyException
     */
    protected function getAccountPeriodOverview(Account $account, Carbon $start, Carbon $end): array
    {
        Timer::start('account-period-total');
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $range                   = app('navigation')->getViewRange(true);
        [$start, $end]           = $end < $start ? [$end, $start] : [$start, $end];

        // properties for cache
        $cache                   = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('account-show-period-entries');
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return $cache->get();
        }

        /** @var array $dates */
        $dates                   = app('navigation')->blockPeriods($start, $end, $range);
        $entries                 = [];

        // run a custom query because doing this with the collector is MEGA slow.
        $transactions            = $this->accountRepository->periodCollection($account, $start, $end);

        // loop dates
        foreach ($dates as $currentDate) {
            $title                            = app('navigation')->periodShow($currentDate['start'], $currentDate['period']);
            [$transactions, $spent]           = $this->filterTransactionsByType(TransactionTypeEnum::WITHDRAWAL, $transactions, $currentDate['start'], $currentDate['end']);
            [$transactions, $earned]          = $this->filterTransactionsByType(TransactionTypeEnum::DEPOSIT, $transactions, $currentDate['start'], $currentDate['end']);
            [$transactions, $transferredAway] = $this->filterTransfers('away', $transactions, $currentDate['start'], $currentDate['end']);
            [$transactions, $transferredIn]   = $this->filterTransfers('in', $transactions, $currentDate['start'], $currentDate['end']);
            $entries[]
                                              = [
                                                  'title'              => $title,
                                                  'route'              => route('accounts.show', [$account->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                                                  'total_transactions' => count($spent) + count($earned) + count($transferredAway) + count($transferredIn),
                                                  'spent'              => $this->groupByCurrency($spent),
                                                  'earned'             => $this->groupByCurrency($earned),
                                                  'transferred_away'   => $this->groupByCurrency($transferredAway),
                                                  'transferred_in'     => $this->groupByCurrency($transferredIn),
                                              ];
        }
        $cache->store($entries);
        Timer::stop('account-period-total');

        return $entries;
    }

    private function filterTransactionsByType(TransactionTypeEnum $type, array $transactions, Carbon $start, Carbon $end): array
    {
        $result = [];

        /**
         * @var int   $index
         * @var array $item
         */
        foreach ($transactions as $index => $item) {
            $date = Carbon::parse($item['date']);
            if ($item['type'] === $type->value && $date >= $start && $date <= $end) {
                $result[] = $item;
                unset($transactions[$index]);
            }
        }

        return [$transactions, $result];
    }

    private function filterTransfers(string $direction, array $transactions, Carbon $start, Carbon $end): array
    {
        $result = [];

        /**
         * @var int   $index
         * @var array $item
         */
        foreach ($transactions as $index => $item) {
            $date = Carbon::parse($item['date']);
            if ($date >= $start && $date <= $end) {
                if ('away' === $direction && -1 === bccomp((string) $item['amount'], '0')) {
                    $result[] = $item;
                    unset($transactions[$index]);
                }
                if ('in' === $direction && 1 === bccomp((string) $item['amount'], '0')) {
                    $result[] = $item;
                    unset($transactions[$index]);
                }
            }
        }

        return [$transactions, $result];
    }

    private function groupByCurrency(array $journals): array
    {
        $return = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $currencyId                    = (int) $journal['currency_id'];
            $currencyCode                  = $journal['currency_code'];
            $currencyName                  = $journal['currency_name'];
            $currencySymbol                = $journal['currency_symbol'];
            $currencyDecimalPlaces         = $journal['currency_decimal_places'];
            $foreignCurrencyId             = $journal['foreign_currency_id'];
            $amount                        = $journal['amount'] ?? '0';

            if ($this->convertToNative && $currencyId !== $this->defaultCurrency->id && $foreignCurrencyId !== $this->defaultCurrency->id) {
                $amount                = $journal['native_amount'] ?? '0';
                $currencyId            = $this->defaultCurrency->id;
                $currencyCode          = $this->defaultCurrency->code;
                $currencyName          = $this->defaultCurrency->name;
                $currencySymbol        = $this->defaultCurrency->symbol;
                $currencyDecimalPlaces = $this->defaultCurrency->decimal_places;
            }
            if ($this->convertToNative && $currencyId !== $this->defaultCurrency->id && $foreignCurrencyId === $this->defaultCurrency->id) {
                $currencyId            = (int) $foreignCurrencyId;
                $currencyCode          = $journal['foreign_currency_code'];
                $currencyName          = $journal['foreign_currency_name'];
                $currencySymbol        = $journal['foreign_currency_symbol'];
                $currencyDecimalPlaces = $journal['foreign_currency_decimal_places'];
                $amount                = $journal['foreign_amount'] ?? '0';
            }
            $return[$currencyId] ??= [
                'amount'                  => '0',
                'count'                   => 0,
                'currency_id'             => $currencyId,
                'currency_name'           => $currencyName,
                'currency_code'           => $currencyCode,
                'currency_symbol'         => $currencySymbol,
                'currency_decimal_places' => $currencyDecimalPlaces,
            ];


            $return[$currencyId]['amount'] = bcadd($return[$currencyId]['amount'], $amount);
            ++$return[$currencyId]['count'];
        }

        return $return;
    }

    /**
     * Overview for single category. Has been refactored recently.
     *
     * @throws FireflyException
     */
    protected function getCategoryPeriodOverview(Category $category, Carbon $start, Carbon $end): array
    {
        $range         = app('navigation')->getViewRange(true);
        [$start, $end] = $end < $start ? [$end, $start] : [$start, $end];

        // properties for entries with their amounts.
        $cache         = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($range);
        $cache->addProperty('category-show-period-entries');
        $cache->addProperty($category->id);

        if ($cache->has()) {
            return $cache->get();
        }

        /** @var array $dates */
        $dates         = app('navigation')->blockPeriods($start, $end, $range);
        $entries       = [];

        // collect all expenses in this period:
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $collector->setCategory($category);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::DEPOSIT->value]);
        $earnedSet     = $collector->getExtractedJournals();

        // collect all income in this period:
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $collector->setCategory($category);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
        $spentSet      = $collector->getExtractedJournals();

        // collect all transfers in this period:
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $collector->setCategory($category);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::TRANSFER->value]);
        $transferSet   = $collector->getExtractedJournals();
        foreach ($dates as $currentDate) {
            $spent       = $this->filterJournalsByDate($spentSet, $currentDate['start'], $currentDate['end']);
            $earned      = $this->filterJournalsByDate($earnedSet, $currentDate['start'], $currentDate['end']);
            $transferred = $this->filterJournalsByDate($transferSet, $currentDate['start'], $currentDate['end']);
            $title       = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries[]
                         = [
                             'transactions'       => 0,
                             'title'              => $title,
                             'route'              => route(
                                 'categories.show',
                                 [$category->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]
                             ),
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
     * Filter a list of journals by a set of dates, and then group them by currency.
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
     * Same as above, but for lists that involve transactions without a budget.
     *
     * This method has been refactored recently.
     *
     * @throws FireflyException
     */
    protected function getNoBudgetPeriodOverview(Carbon $start, Carbon $end): array
    {
        $range         = app('navigation')->getViewRange(true);

        [$start, $end] = $end < $start ? [$end, $start] : [$start, $end];

        $cache         = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($this->convertToNative);
        $cache->addProperty('no-budget-period-entries');

        if ($cache->has()) {
            return $cache->get();
        }

        /** @var array $dates */
        $dates         = app('navigation')->blockPeriods($start, $end, $range);
        $entries       = [];

        // get all expenses without a budget.
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->withoutBudget()->withAccountInformation()->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
        $journals      = $collector->getExtractedJournals();

        foreach ($dates as $currentDate) {
            $set   = $this->filterJournalsByDate($journals, $currentDate['start'], $currentDate['end']);
            $title = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries[]
                   = [
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
     * TODO fix the date.
     *
     * Show period overview for no category view.
     *
     * @throws FireflyException
     */
    protected function getNoCategoryPeriodOverview(Carbon $theDate): array
    {
        app('log')->debug(sprintf('Now in getNoCategoryPeriodOverview(%s)', $theDate->format('Y-m-d')));
        $range       = app('navigation')->getViewRange(true);
        $first       = $this->journalRepos->firstNull();
        $start       = null === $first ? new Carbon() : $first->date;
        $end         = clone $theDate;
        $end         = app('navigation')->endOfPeriod($end, $range);

        app('log')->debug(sprintf('Start for getNoCategoryPeriodOverview() is %s', $start->format('Y-m-d')));
        app('log')->debug(sprintf('End for getNoCategoryPeriodOverview() is %s', $end->format('Y-m-d')));

        // properties for cache
        $dates       = app('navigation')->blockPeriods($start, $end, $range);
        $entries     = [];

        // collect all expenses in this period:
        /** @var GroupCollectorInterface $collector */
        $collector   = app(GroupCollectorInterface::class);
        $collector->withoutCategory();
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::DEPOSIT->value]);
        $earnedSet   = $collector->getExtractedJournals();

        // collect all income in this period:
        /** @var GroupCollectorInterface $collector */
        $collector   = app(GroupCollectorInterface::class);
        $collector->withoutCategory();
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
        $spentSet    = $collector->getExtractedJournals();

        // collect all transfers in this period:
        /** @var GroupCollectorInterface $collector */
        $collector   = app(GroupCollectorInterface::class);
        $collector->withoutCategory();
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::TRANSFER->value]);
        $transferSet = $collector->getExtractedJournals();

        /** @var array $currentDate */
        foreach ($dates as $currentDate) {
            $spent       = $this->filterJournalsByDate($spentSet, $currentDate['start'], $currentDate['end']);
            $earned      = $this->filterJournalsByDate($earnedSet, $currentDate['start'], $currentDate['end']);
            $transferred = $this->filterJournalsByDate($transferSet, $currentDate['start'], $currentDate['end']);
            $title       = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries[]
                         = [
                             'title'              => $title,
                             'route'              => route('categories.no-category', [$currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                             'total_transactions' => count($spent) + count($earned) + count($transferred),
                             'spent'              => $this->groupByCurrency($spent),
                             'earned'             => $this->groupByCurrency($earned),
                             'transferred'        => $this->groupByCurrency($transferred),
                         ];
        }
        app('log')->debug('End of loops');

        return $entries;
    }

    /**
     * This shows a period overview for a tag. It goes back in time and lists all relevant transactions and sums.
     *
     * @throws FireflyException
     */
    protected function getTagPeriodOverview(Tag $tag, Carbon $start, Carbon $end): array // period overview for tags.
    {
        $range         = app('navigation')->getViewRange(true);
        [$start, $end] = $end < $start ? [$end, $start] : [$start, $end];

        // properties for cache
        $cache         = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('tag-period-entries');
        $cache->addProperty($tag->id);
        if ($cache->has()) {
            return $cache->get();
        }

        /** @var array $dates */
        $dates         = app('navigation')->blockPeriods($start, $end, $range);
        $entries       = [];

        // collect all expenses in this period:
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $collector->setTag($tag);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::DEPOSIT->value]);
        $earnedSet     = $collector->getExtractedJournals();

        // collect all income in this period:
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $collector->setTag($tag);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
        $spentSet      = $collector->getExtractedJournals();

        // collect all transfers in this period:
        /** @var GroupCollectorInterface $collector */
        $collector     = app(GroupCollectorInterface::class);
        $collector->setTag($tag);
        $collector->setRange($start, $end);
        $collector->setTypes([TransactionTypeEnum::TRANSFER->value]);
        $transferSet   = $collector->getExtractedJournals();

        // filer all of them:
        $earnedSet     = $this->filterJournalsByTag($earnedSet, $tag);
        $spentSet      = $this->filterJournalsByTag($spentSet, $tag);
        $transferSet   = $this->filterJournalsByTag($transferSet, $tag);

        foreach ($dates as $currentDate) {
            $spent       = $this->filterJournalsByDate($spentSet, $currentDate['start'], $currentDate['end']);
            $earned      = $this->filterJournalsByDate($earnedSet, $currentDate['start'], $currentDate['end']);
            $transferred = $this->filterJournalsByDate($transferSet, $currentDate['start'], $currentDate['end']);
            $title       = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);
            $entries[]
                         = [
                             'transactions'       => 0,
                             'title'              => $title,
                             'route'              => route(
                                 'tags.show',
                                 [$tag->id, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]
                             ),
                             'total_transactions' => count($spent) + count($earned) + count($transferred),
                             'spent'              => $this->groupByCurrency($spent),
                             'earned'             => $this->groupByCurrency($earned),
                             'transferred'        => $this->groupByCurrency($transferred),
                         ];
        }

        return $entries;
    }

    private function filterJournalsByTag(array $set, Tag $tag): array
    {
        $return = [];
        foreach ($set as $entry) {
            $found    = false;

            /** @var array $localTag */
            foreach ($entry['tags'] as $localTag) {
                if ($localTag['id'] === $tag->id) {
                    $found = true;
                }
            }
            if (false === $found) {
                continue;
            }
            $return[] = $entry;
        }

        return $return;
    }

    /**
     * @throws FireflyException
     */
    protected function getTransactionPeriodOverview(string $transactionType, Carbon $start, Carbon $end): array
    {
        $range         = app('navigation')->getViewRange(true);
        $types         = config(sprintf('firefly.transactionTypesByType.%s', $transactionType));
        [$start, $end] = $end < $start ? [$end, $start] : [$start, $end];

        // properties for cache
        $cache         = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('transactions-period-entries');
        $cache->addProperty($transactionType);
        if ($cache->has()) {
            return $cache->get();
        }

        /** @var array $dates */
        $dates         = app('navigation')->blockPeriods($start, $end, $range);
        $entries       = [];

        // collect all journals in this period (regardless of type)
        $collector     = app(GroupCollectorInterface::class);
        $collector->setTypes($types)->setRange($start, $end);
        $genericSet    = $collector->getExtractedJournals();

        foreach ($dates as $currentDate) {
            $spent       = [];
            $earned      = [];
            $transferred = [];
            $title       = app('navigation')->periodShow($currentDate['end'], $currentDate['period']);

            // set to correct array
            if ('expenses' === $transactionType || 'withdrawal' === $transactionType) {
                $spent = $this->filterJournalsByDate($genericSet, $currentDate['start'], $currentDate['end']);
            }
            if ('revenue' === $transactionType || 'deposit' === $transactionType) {
                $earned = $this->filterJournalsByDate($genericSet, $currentDate['start'], $currentDate['end']);
            }
            if ('transfer' === $transactionType || 'transfers' === $transactionType) {
                $transferred = $this->filterJournalsByDate($genericSet, $currentDate['start'], $currentDate['end']);
            }
            $entries[]
                         = [
                             'title'              => $title,
                             'route'              => route('transactions.index', [$transactionType, $currentDate['start']->format('Y-m-d'), $currentDate['end']->format('Y-m-d')]),
                             'total_transactions' => count($spent) + count($earned) + count($transferred),
                             'spent'              => $this->groupByCurrency($spent),
                             'earned'             => $this->groupByCurrency($earned),
                             'transferred'        => $this->groupByCurrency($transferred),
                         ];
        }

        return $entries;
    }

    /**
     * Return only transactions where $account is the source.
     */
    private function filterTransferredAway(Account $account, array $journals): array
    {
        $return = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            if ($account->id === (int) $journal['source_account_id']) {
                $return[] = $journal;
            }
        }

        return $return;
    }

    /**
     * Return only transactions where $account is the source.
     */
    private function filterTransferredIn(Account $account, array $journals): array
    {
        $return = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            if ($account->id === (int) $journal['destination_account_id']) {
                $return[] = $journal;
            }
        }

        return $return;
    }
}
