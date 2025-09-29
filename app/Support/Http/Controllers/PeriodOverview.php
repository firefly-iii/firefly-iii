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
use FireflyIII\Models\PeriodStatistic;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PeriodStatistic\PeriodStatisticRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Navigation;
use FireflyIII\Support\Facades\Steam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    protected AccountRepositoryInterface         $accountRepository;
    protected CategoryRepositoryInterface        $categoryRepository;
    protected TagRepositoryInterface             $tagRepository;
    protected JournalRepositoryInterface         $journalRepos;
    protected PeriodStatisticRepositoryInterface $periodStatisticRepo;
    private Collection                           $statistics;   // temp data holder
    private array                                $transactions; // temp data holder

    /**
     * This method returns "period entries", so nov-2015, dec-2015, etc. (this depends on the users session range)
     * and for each period, the amount of money spent and earned. This is a complex operation which is cached for
     * performance reasons.
     *
     * @throws FireflyException
     */
    protected function getAccountPeriodOverview(Account $account, Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Now in getAccountPeriodOverview(#%d, %s %s)', $account->id, $start->format('Y-m-d H:i:s.u'), $end->format('Y-m-d H:i:s.u')));
        $this->accountRepository   = app(AccountRepositoryInterface::class);
        $this->accountRepository->setUser($account->user);
        $this->periodStatisticRepo = app(PeriodStatisticRepositoryInterface::class);
        $range                     = Navigation::getViewRange(true);
        [$start, $end]             = $end < $start ? [$end, $start] : [$start, $end];

        /** @var array $dates */
        $dates                     = Navigation::blockPeriods($start, $end, $range);
        [$start, $end]             = $this->getPeriodFromBlocks($dates, $start, $end);
        $this->statistics          = $this->periodStatisticRepo->allInRangeForModel($account, $start, $end);

        $entries                   = [];
        Log::debug(sprintf('Count of loops: %d', count($dates)));
        foreach ($dates as $currentDate) {
            $entries[] = $this->getSingleModelPeriod($account, $currentDate['period'], $currentDate['start'], $currentDate['end']);
        }
        Log::debug('End of getAccountPeriodOverview()');

        return $entries;
    }

    private function getPeriodFromBlocks(array $dates, Carbon $start, Carbon $end): array
    {
        Log::debug('Filter generated periods to select the oldest and newest date.');
        foreach ($dates as $row) {
            $currentStart = clone $row['start'];
            $currentEnd   = clone $row['end'];
            if ($currentStart->lt($start)) {
                Log::debug(sprintf('New start: was %s, now %s', $start->format('Y-m-d'), $currentStart->format('Y-m-d')));
                $start = $currentStart;
            }
            if ($currentEnd->gt($end)) {
                Log::debug(sprintf('New end: was %s, now %s', $end->format('Y-m-d'), $currentEnd->format('Y-m-d')));
                $end = $currentEnd;
            }
        }

        return [$start, $end];
    }

    /**
     * Overview for single category. Has been refactored recently.
     *
     * @throws FireflyException
     */
    protected function getCategoryPeriodOverview(Category $category, Carbon $start, Carbon $end): array
    {
        $this->categoryRepository  = app(CategoryRepositoryInterface::class);
        $this->categoryRepository->setUser($category->user);
        $this->periodStatisticRepo = app(PeriodStatisticRepositoryInterface::class);

        $range                     = Navigation::getViewRange(true);
        [$start, $end]             = $end < $start ? [$end, $start] : [$start, $end];

        /** @var array $dates */
        $dates                     = Navigation::blockPeriods($start, $end, $range);
        $entries                   = [];
        [$start, $end]             = $this->getPeriodFromBlocks($dates, $start, $end);
        $this->statistics          = $this->periodStatisticRepo->allInRangeForModel($category, $start, $end);


        Log::debug(sprintf('Count of loops: %d', count($dates)));
        foreach ($dates as $currentDate) {
            $entries[] = $this->getSingleModelPeriod($category, $currentDate['period'], $currentDate['start'], $currentDate['end']);
        }

        return $entries;
    }

    /**
     * Same as above, but for lists that involve transactions without a budget.
     *
     * This method has been refactored recently.
     *
     * @throws FireflyException
     */
    protected function getNoModelPeriodOverview(string $model, Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Now in getNoModelPeriodOverview(%s, %s %s)', $model, $start->format('Y-m-d'), $end->format('Y-m-d')));
        $this->periodStatisticRepo = app(PeriodStatisticRepositoryInterface::class);
        $range                     = Navigation::getViewRange(true);
        [$start, $end]             = $end < $start ? [$end, $start] : [$start, $end];

        /** @var array $dates */
        $dates                     = Navigation::blockPeriods($start, $end, $range);
        [$start, $end]             = $this->getPeriodFromBlocks($dates, $start, $end);
        $entries                   = [];
        $this->statistics          = $this->periodStatisticRepo->allInRangeForPrefix(sprintf('no_%s', $model), $start, $end);
        Log::debug(sprintf('Collected %d stats', $this->statistics->count()));

        foreach ($dates as $currentDate) {
            $entries[] = $this->getSingleNoModelPeriodOverview($model, $currentDate['start'], $currentDate['end'], $currentDate['period']);
        }

        return $entries;
    }

    private function getSingleNoModelPeriodOverview(string $model, Carbon $start, Carbon $end, string $period): array
    {
        Log::debug(sprintf('getSingleNoModelPeriodOverview(%s, %s, %s, %s)', $model, $start->format('Y-m-d'), $end->format('Y-m-d'), $period));
        $statistics = $this->filterPrefixedStatistics($start, $end, sprintf('no_%s', $model));
        $title      = Navigation::periodShow($end, $period);

        if (0 === $statistics->count()) {
            Log::debug(sprintf('Found no statistics in period %s - %s, regenerating them.', $start->format('Y-m-d'), $end->format('Y-m-d')));

            switch ($model) {
                default:
                    throw new FireflyException(sprintf('Cannot deal with model of type "%s"', $model));

                case 'budget':
                    // get all expenses without a budget.
                    /** @var GroupCollectorInterface $collector */
                    $collector   = app(GroupCollectorInterface::class);
                    $collector->setRange($start, $end)->withoutBudget()->withAccountInformation()->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
                    $spent       = $collector->getExtractedJournals();
                    $earned      = [];
                    $transferred = [];

                    break;

                case 'category':
                    // collect all expenses in this period:
                    /** @var GroupCollectorInterface $collector */
                    $collector   = app(GroupCollectorInterface::class);
                    $collector->withoutCategory();
                    $collector->setRange($start, $end);
                    $collector->setTypes([TransactionTypeEnum::DEPOSIT->value]);
                    $earned      = $collector->getExtractedJournals();

                    // collect all income in this period:
                    /** @var GroupCollectorInterface $collector */
                    $collector   = app(GroupCollectorInterface::class);
                    $collector->withoutCategory();
                    $collector->setRange($start, $end);
                    $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
                    $spent       = $collector->getExtractedJournals();

                    // collect all transfers in this period:
                    /** @var GroupCollectorInterface $collector */
                    $collector   = app(GroupCollectorInterface::class);
                    $collector->withoutCategory();
                    $collector->setRange($start, $end);
                    $collector->setTypes([TransactionTypeEnum::TRANSFER->value]);
                    $transferred = $collector->getExtractedJournals();

                    break;
            }
            $groupedSpent       = $this->groupByCurrency($spent);
            $groupedEarned      = $this->groupByCurrency($earned);
            $groupedTransferred = $this->groupByCurrency($transferred);
            $entry
                                = [
                                    'title'              => $title,
                                    'route'              => route(sprintf('%s.no-%s', Str::plural($model), $model), [$start->format('Y-m-d'), $end->format('Y-m-d')]),
                                    'total_transactions' => count($spent),
                                    'spent'              => $groupedSpent,
                                    'earned'             => $groupedEarned,
                                    'transferred'        => $groupedTransferred,
                                ];
            $this->saveGroupedForPrefix(sprintf('no_%s', $model), $start, $end, 'spent', $groupedSpent);
            $this->saveGroupedForPrefix(sprintf('no_%s', $model), $start, $end, 'earned', $groupedEarned);
            $this->saveGroupedForPrefix(sprintf('no_%s', $model), $start, $end, 'transferred', $groupedTransferred);

            return $entry;
        }
        Log::debug(sprintf('Found %d statistics in period %s - %s.', count($statistics), $start->format('Y-m-d'), $end->format('Y-m-d')));

        $entry
                    = [
                     'title'              => $title,
                     'route'              => route(sprintf('%s.no-%s', Str::plural($model), $model), [$start->format('Y-m-d'), $end->format('Y-m-d')]),
                     'total_transactions' => 0,
                     'spent'              => [],
                     'earned'             => [],
                     'transferred'        => [],
                 ];
        $grouped    = [];

        /** @var PeriodStatistic $statistic */
        foreach ($statistics as $statistic) {
            $type                = str_replace(sprintf('no_%s_', $model), '', $statistic->type);
            $id                  = (int)$statistic->transaction_currency_id;
            $currency            = Amount::getTransactionCurrencyById($id);
            $grouped[$type]['count'] ??= 0;
            $grouped[$type][$id] = [
                'amount'                  => (string)$statistic->amount,
                'count'                   => (int)$statistic->count,
                'currency_id'             => $currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
            ];
            $grouped[$type]['count'] += (int)$statistic->count;
        }
        $types      = ['spent', 'earned', 'transferred'];
        foreach ($types as $type) {
            if (array_key_exists($type, $grouped)) {
                $entry['total_transactions'] += $grouped[$type]['count'];
                unset($grouped[$type]['count']);
                $entry[$type] = $grouped[$type];
            }

        }

        return $entry;
    }

    protected function getSingleModelPeriod(Model $model, string $period, Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Now in getSingleModelPeriod(%s #%d, %s %s)', $model::class, $model->id, $start->format('Y-m-d'), $end->format('Y-m-d')));
        $types              = ['spent', 'earned', 'transferred_in', 'transferred_away'];
        $return             = [
            'title'              => Navigation::periodShow($start, $period),
            'route'              => route(sprintf('%s.show', strtolower(Str::plural(class_basename($model)))), [$model->id, $start->format('Y-m-d'), $end->format('Y-m-d')]),
            'total_transactions' => 0,
        ];
        $this->transactions = [];
        foreach ($types as $type) {
            $set           = $this->getSingleModelPeriodByType($model, $start, $end, $type);
            $return['total_transactions'] += $set['count'];
            unset($set['count']);
            $return[$type] = $set;
        }

        return $return;
    }

    private function filterStatistics(Carbon $start, Carbon $end, string $type): Collection
    {
        if (0 === $this->statistics->count()) {
            Log::warning('Have no statistic to filter!');

            return new Collection();
        }

        return $this->statistics->filter(
            function (PeriodStatistic $statistic) use ($start, $end, $type) {
                return $statistic->start->eq($start) && $statistic->end->eq($end) && $statistic->type === $type;
            }
        );
    }

    private function filterPrefixedStatistics(Carbon $start, Carbon $end, string $prefix): Collection
    {
        if (0 === $this->statistics->count()) {
            Log::warning('Have no statistic to filter!');

            return new Collection();
        }

        return $this->statistics->filter(
            function (PeriodStatistic $statistic) use ($start, $end, $prefix) {
                return $statistic->start->eq($start) && $statistic->end->eq($end) && str_starts_with($statistic->type, $prefix);
            }
        );
    }

    private function getSingleModelPeriodByType(Model $model, Carbon $start, Carbon $end, string $type): array
    {
        Log::debug(sprintf('Now in getSingleModelPeriodByType(%s #%d, %s %s, %s)', $model::class, $model->id, $start->format('Y-m-d'), $end->format('Y-m-d'), $type));
        $statistics = $this->filterStatistics($start, $end, $type);

        // nothing found, regenerate them.
        if (0 === $statistics->count()) {
            Log::debug(sprintf('Found nothing in this period for type "%s"', $type));
            if (0 === count($this->transactions)) {
                switch ($model::class) {
                    default:
                        throw new FireflyException(sprintf('Cannot deal with model of type "%s"', $model::class));

                    case Category::class:
                        $this->transactions = $this->categoryRepository->periodCollection($model, $start, $end);

                        break;

                    case Account::class:
                        $this->transactions = $this->accountRepository->periodCollection($model, $start, $end);

                        break;

                    case Tag::class:
                        $this->transactions = $this->tagRepository->periodCollection($model, $start, $end);

                        break;
                }
            }

            switch ($type) {
                default:
                    throw new FireflyException(sprintf('Cannot deal with category period type %s', $type));

                case 'spent':

                    $result = $this->filterTransactionsByType(TransactionTypeEnum::WITHDRAWAL, $start, $end);

                    break;

                case 'earned':
                    $result = $this->filterTransactionsByType(TransactionTypeEnum::DEPOSIT, $start, $end);

                    break;

                case 'transferred_in':
                    $result = $this->filterTransfers('in', $start, $end);

                    break;

                case 'transferred_away':
                    $result = $this->filterTransfers('away', $start, $end);

                    break;
            }
            // each result must be grouped by currency, then saved as period statistic.
            Log::debug(sprintf('Going to group %d found journal(s)', count($result)));
            $grouped = $this->groupByCurrency($result);

            $this->saveGroupedAsStatistics($model, $start, $end, $type, $grouped);

            return $grouped;
        }
        $grouped    = [
            'count' => 0,
        ];

        /** @var PeriodStatistic $statistic */
        foreach ($statistics as $statistic) {
            $id           = (int)$statistic->transaction_currency_id;
            $currency     = Amount::getTransactionCurrencyById($id);
            $grouped[$id] = [
                'amount'                  => (string)$statistic->amount,
                'count'                   => (int)$statistic->count,
                'currency_id'             => $currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
            ];
            $grouped['count'] += (int)$statistic->count;
        }

        return $grouped;
    }

    /**
     * This shows a period overview for a tag. It goes back in time and lists all relevant transactions and sums.
     *
     * @throws FireflyException
     */
    protected function getTagPeriodOverview(Tag $tag, Carbon $start, Carbon $end): array // period overview for tags.
    {
        $this->tagRepository       = app(TagRepositoryInterface::class);
        $this->tagRepository->setUser($tag->user);
        $this->periodStatisticRepo = app(PeriodStatisticRepositoryInterface::class);

        $range                     = Navigation::getViewRange(true);
        [$start, $end]             = $end < $start ? [$end, $start] : [$start, $end];

        /** @var array $dates */
        $dates                     = Navigation::blockPeriods($start, $end, $range);
        $entries                   = [];
        [$start, $end]             = $this->getPeriodFromBlocks($dates, $start, $end);
        $this->statistics          = $this->periodStatisticRepo->allInRangeForModel($tag, $start, $end);


        Log::debug(sprintf('Count of loops: %d', count($dates)));
        foreach ($dates as $currentDate) {
            $entries[] = $this->getSingleModelPeriod($tag, $currentDate['period'], $currentDate['start'], $currentDate['end']);
        }

        return $entries;
    }

    /**
     * @throws FireflyException
     */
    protected function getTransactionPeriodOverview(string $transactionType, Carbon $start, Carbon $end): array
    {
        $range         = Navigation::getViewRange(true);
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
        $dates         = Navigation::blockPeriods($start, $end, $range);
        $entries       = [];
        $spent         = [];
        $earned        = [];
        $transferred   = [];
        // collect all journals in this period (regardless of type)
        $collector     = app(GroupCollectorInterface::class);
        $collector->setTypes($types)->setRange($start, $end);
        $genericSet    = $collector->getExtractedJournals();
        $loops         = 0;

        foreach ($dates as $currentDate) {
            $title = Navigation::periodShow($currentDate['end'], $currentDate['period']);

            if ($loops < 10) {
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
            ++$loops;
        }

        return $entries;
    }

    private function saveGroupedAsStatistics(Model $model, Carbon $start, Carbon $end, string $type, array $array): void
    {
        unset($array['count']);
        Log::debug(sprintf('saveGroupedAsStatistics(%s #%d, %s, %s, "%s", array(%d))', $model::class, $model->id, $start->format('Y-m-d'), $end->format('Y-m-d'), $type, count($array)));
        foreach ($array as $entry) {
            $this->periodStatisticRepo->saveStatistic($model, $entry['currency_id'], $start, $end, $type, $entry['count'], $entry['amount']);
        }
        if (0 === count($array)) {
            Log::debug('Save empty statistic.');
            $this->periodStatisticRepo->saveStatistic($model, $this->primaryCurrency->id, $start, $end, $type, 0, '0');
        }
    }

    private function saveGroupedForPrefix(string $prefix, Carbon $start, Carbon $end, string $type, array $array): void
    {
        unset($array['count']);
        Log::debug(sprintf('saveGroupedForPrefix("%s", %s, %s, "%s", array(%d))', $prefix, $start->format('Y-m-d'), $end->format('Y-m-d'), $type, count($array)));
        foreach ($array as $entry) {
            $this->periodStatisticRepo->savePrefixedStatistic($prefix, $entry['currency_id'], $start, $end, $type, $entry['count'], $entry['amount']);
        }
        if (0 === count($array)) {
            Log::debug('Save empty statistic.');
            $this->periodStatisticRepo->savePrefixedStatistic($prefix, $this->primaryCurrency->id, $start, $end, $type, 0, '0');
        }
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

    private function filterTransactionsByType(TransactionTypeEnum $type, Carbon $start, Carbon $end): array
    {
        $result = [];

        /**
         * @var int   $index
         * @var array $item
         */
        foreach ($this->transactions as $item) {
            $date = Carbon::parse($item['date']);
            $fits = $item['type'] === $type->value && $date >= $start && $date <= $end;
            if ($fits) {

                // if type is withdrawal, negative amount:
                if (TransactionTypeEnum::WITHDRAWAL === $type && 1 === bccomp((string)$item['amount'], '0')) {
                    $item['amount'] = Steam::negative($item['amount']);
                }
                $result[] = $item;
            }
        }

        return $result;
    }

    private function filterTransfers(string $direction, Carbon $start, Carbon $end): array
    {
        $result = [];

        /**
         * @var int   $index
         * @var array $item
         */
        foreach ($this->transactions as $item) {
            $date = Carbon::parse($item['date']);
            if ($date >= $start && $date <= $end) {
                if ('Transfer' === $item['type'] && 'away' === $direction && -1 === bccomp((string)$item['amount'], '0')) {
                    $result[] = $item;

                    continue;
                }
                if ('Transfer' === $item['type'] && 'in' === $direction && 1 === bccomp((string)$item['amount'], '0')) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    private function groupByCurrency(array $journals): array
    {
        Log::debug('groupByCurrency()');
        $return = [
            'count' => 0,
        ];
        if (0 === count($journals)) {
            return $return;
        }

        /** @var array $journal */
        foreach ($journals as $journal) {
            $currencyId                    = (int)$journal['currency_id'];
            $currencyCode                  = $journal['currency_code'];
            $currencyName                  = $journal['currency_name'];
            $currencySymbol                = $journal['currency_symbol'];
            $currencyDecimalPlaces         = $journal['currency_decimal_places'];
            $foreignCurrencyId             = $journal['foreign_currency_id'];
            $amount                        = $journal['amount'] ?? '0';

            if ($this->convertToPrimary && $currencyId !== $this->primaryCurrency->id && $foreignCurrencyId !== $this->primaryCurrency->id) {
                $amount                = $journal['pc_amount'] ?? '0';
                $currencyId            = $this->primaryCurrency->id;
                $currencyCode          = $this->primaryCurrency->code;
                $currencyName          = $this->primaryCurrency->name;
                $currencySymbol        = $this->primaryCurrency->symbol;
                $currencyDecimalPlaces = $this->primaryCurrency->decimal_places;
            }
            if ($this->convertToPrimary && $currencyId !== $this->primaryCurrency->id && $foreignCurrencyId === $this->primaryCurrency->id) {
                $currencyId            = (int)$foreignCurrencyId;
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


            $return[$currencyId]['amount'] = bcadd((string)$return[$currencyId]['amount'], $amount);
            ++$return[$currencyId]['count'];
            ++$return['count'];
        }

        return $return;
    }
}
