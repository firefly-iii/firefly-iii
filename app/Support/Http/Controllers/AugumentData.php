<?php
/**
 * AugumentData.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Trait AugumentData
 *
 */
trait AugumentData
{
    /**
     * Searches for the opposing account.
     *
     * @param Collection $accounts
     *
     * @return array
     */
    protected function combineAccounts(Collection $accounts): array // filter + group data
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $combined   = [];
        /** @var Account $expenseAccount */
        foreach ($accounts as $expenseAccount) {
            $collection = new Collection;
            $collection->push($expenseAccount);

            $revenue = $repository->findByName($expenseAccount->name, [AccountType::REVENUE]);
            if (null !== $revenue) {
                $collection->push($revenue);
            }
            $combined[$expenseAccount->name] = $collection;
        }

        return $combined;
    }

    /**
     * Group by category (earnings).
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     */
    protected function earnedByCategory(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $total = $assets->merge($opposing);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setAccounts($total);
        $collector->withCategoryInformation();
        $journals = $collector->getExtractedJournals();
        $sum      = [];
        // loop to support multi currency
        foreach ($journals as $journal) {
            $currencyId   = $journal['currency_id'];
            $categoryName = $journal['category_name'];
            $categoryId   = (int)$journal['category_id'];

            // if not set, set to zero:
            if (!isset($sum[$categoryId][$currencyId])) {
                $sum[$categoryId] = [
                    'grand_total'  => '0',
                    'name'         => $categoryName,
                    'per_currency' => [
                        $currencyId => [
                            'sum'      => '0',
                            'category' => [
                                'id'   => $categoryId,
                                'name' => $categoryName,
                            ],
                            'currency' => [
                                'symbol' => $journal['currency_symbol'],
                                'dp'     => $journal['currency_decimal_places'],
                            ],
                        ],
                    ],
                ];
            }

            // add amount
            $sum[$categoryId]['per_currency'][$currencyId]['sum'] = bcadd(
                $sum[$categoryId]['per_currency'][$currencyId]['sum'], $journal['amount']
            );
            $sum[$categoryId]['grand_total']                      = bcadd($sum[$categoryId]['grand_total'], $journal['amount']);
        }

        return $sum;
    }

    /**
     * Earned in period for accounts.
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    protected function earnedInPeriod(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $total = $assets->merge($opposing);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setAccounts($total);
        $journals = $collector->getExtractedJournals();
        $sum      = [
            'grand_sum'    => '0',
            'per_currency' => [],
        ];
        // loop to support multi currency
        foreach ($journals as $journal) {
            $currencyId = (int)$journal['currency_id'];

            // if not set, set to zero:
            if (!isset($sum['per_currency'][$currencyId])) {
                $sum['per_currency'][$currencyId] = [
                    'sum'      => '0',
                    'currency' => [
                        'symbol'         => $journal['currency_symbol'],
                        'decimal_places' => $journal['currency_decimal_places'],
                    ],
                ];
            }

            // add amount
            $sum['per_currency'][$currencyId]['sum'] = bcadd($sum['per_currency'][$currencyId]['sum'], $journal['amount']);
            $sum['grand_sum']                        = bcadd($sum['grand_sum'], $journal['amount']);
        }

        return $sum;
    }

    /**
     * Small helper function for the revenue and expense account charts.
     *
     * @param array $names
     *
     * @return array
     */
    protected function expandNames(array $names): array
    {
        $result = [];
        foreach ($names as $entry) {
            $result[$entry['name']] = 0;
        }

        return $result;
    }

    /**
     * Small helper function for the revenue and expense account charts.
     *
     * @param Collection $accounts
     *
     * @return array
     */
    protected function extractNames(Collection $accounts): array
    {
        $return = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $return[$account->id] = $account->name;
        }

        return $return;
    }

    /**
     * Returns the budget limits belonging to the given budget and valid on the given day.
     *
     * @param Collection $budgetLimits
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    protected function filterBudgetLimits(Collection $budgetLimits, Budget $budget, Carbon $start, Carbon $end): Collection // filter data
    {
        $set = $budgetLimits->filter(
            static function (BudgetLimit $budgetLimit) use ($budget, $start, $end) {
                if ($budgetLimit->budget_id === $budget->id
                    && $budgetLimit->start_date->lte($start) // start of budget limit is on or before start
                    && $budgetLimit->end_date->gte($end) // end of budget limit is on or after end
                ) {
                    return $budgetLimit;
                }

                return false;
            }
        );

        return $set;
    }

    /**
     * Get the account names belonging to a bunch of account ID's.
     *
     * @param array $accountIds
     *
     * @return array
     */
    protected function getAccountNames(array $accountIds): array // extract info from array.
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $accounts   = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT, AccountType::EXPENSE, AccountType::CASH]);
        $grouped    = $accounts->groupBy('id')->toArray();
        $return     = [];
        foreach ($accountIds as $accountId) {
            if (isset($grouped[$accountId])) {
                $return[$accountId] = $grouped[$accountId][0]['name'];
            }
        }
        $return[0] = '(no name)';

        return $return;
    }

    /**
     * Get the budget names from a set of budget ID's.
     *
     * @param array $budgetIds
     *
     * @return array
     */
    protected function getBudgetNames(array $budgetIds): array // extract info from array.
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getBudgets();
        $grouped    = $budgets->groupBy('id')->toArray();
        $return     = [];
        foreach ($budgetIds as $budgetId) {
            if (isset($grouped[$budgetId])) {
                $return[$budgetId] = $grouped[$budgetId][0]['name'];
            }
        }
        $return[0] = (string)trans('firefly.no_budget');

        return $return;
    }

    /**
     * Get the amount of money budgeted in a period.
     *
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    protected function getBudgetedInPeriod(Budget $budget, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var BudgetLimitRepositoryInterface $blRepository */
        $blRepository = app(BudgetLimitRepositoryInterface::class);

        $key      = app('navigation')->preferredCarbonFormat($start, $end);
        $range    = app('navigation')->preferredRangeFormat($start, $end);
        $current  = clone $start;
        $budgeted = [];
        while ($current < $end) {
            /** @var Carbon $currentStart */
            $currentStart = app('navigation')->startOfPeriod($current, $range);
            /** @var Carbon $currentEnd */
            $currentEnd       = app('navigation')->endOfPeriod($current, $range);
            $budgetLimits     = $blRepository->getBudgetLimits($budget, $currentStart, $currentEnd);
            $index            = $currentStart->format($key);
            $budgeted[$index] = $budgetLimits->sum('amount');
            $currentEnd->addDay();
            $current = clone $currentEnd;
        }

        return $budgeted;
    }

    /**
     * Get the category names from a set of category ID's. Small helper function for some of the charts.
     *
     * @param array $categoryIds
     *
     * @return array
     */
    protected function getCategoryNames(array $categoryIds): array // extract info from array.
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $categories = $repository->getCategories();
        $grouped    = $categories->groupBy('id')->toArray();
        $return     = [];
        foreach ($categoryIds as $categoryId) {
            if (isset($grouped[$categoryId])) {
                $return[$categoryId] = $grouped[$categoryId][0]['name'];
            }
        }
        $return[0] = (string)trans('firefly.no_category');

        return $return;
    }

    /**
     * Get the expenses for a budget in a date range.
     *
     * @param Collection $limits
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     */
    protected function getExpensesForBudget(Collection $limits, Budget $budget, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);

        $return = [];
        if (0 === $limits->count()) {
            $spent = $opsRepository->spentInPeriod(new Collection([$budget]), new Collection, $start, $end);
            if (0 !== bccomp($spent, '0')) {
                $return[$budget->name]['spent']     = bcmul($spent, '-1');
                $return[$budget->name]['left']      = 0;
                $return[$budget->name]['overspent'] = 0;
            }

            return $return;
        }

        $rows = $this->spentInPeriodMulti($budget, $limits);
        foreach ($rows as $name => $row) {
            if (0 !== bccomp($row['spent'], '0') || 0 !== bccomp($row['left'], '0')) {
                $return[$name] = $row;
            }
        }
        unset($rows);

        return $return;
    }

    /**
     * Gets all budget limits for a budget.
     *
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    protected function getLimits(Budget $budget, Carbon $start, Carbon $end): Collection // get data + augment with info
    {
        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);

        /** @var BudgetLimitRepositoryInterface $blRepository */
        $blRepository = app(BudgetLimitRepositoryInterface::class);

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($budget->id);
        $cache->addProperty('get-limits');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $set    = $blRepository->getBudgetLimits($budget, $start, $end);
        $limits = new Collection();

        /** @var BudgetLimit $entry */
        foreach ($set as $entry) {
            $entry->spent = $opsRepository->spentInPeriod(new Collection([$budget]), new Collection(), $entry->start_date, $entry->end_date);
            $limits->push($entry);
        }
        $cache->store($limits);

        return $set;
    }

    /**
     * Helper function that groups expenses.
     *
     * @param array $array
     *
     * @return array
     */
    protected function groupByBudget(array $array): array // filter + group data
    {
        // group by category ID:
        $grouped = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            $budgetId           = (int)$journal['budget_id'];
            $grouped[$budgetId] = $grouped[$budgetId] ?? '0';
            $grouped[$budgetId] = bcadd($journal['amount'], $grouped[$budgetId]);
        }

        return $grouped;
    }

    /**
     * Group transactions by category.
     *
     * @param array $array
     *
     * @return array
     */
    protected function groupByCategory(array $array): array // filter + group data
    {
        // group by category ID:
        $grouped = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            $categoryId           = (int)$journal['category_id'];
            $grouped[$categoryId] = $grouped[$categoryId] ?? '0';
            $grouped[$categoryId] = bcadd($journal['amount'], $grouped[$categoryId]);
        }

        return $grouped;
    }

    /**
     * Group set of transactions by name of opposing account.
     *
     * @param array $array
     *
     * @return array
     */
    protected function groupByName(array $array): array // filter + group data
    {

        // group by opposing account name.
        $grouped = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            $name = '(no name)';
            if (TransactionType::WITHDRAWAL === $journal['transaction_type_type']) {
                $name = $journal['destination_account_name'];
            }
            if (TransactionType::WITHDRAWAL !== $journal['transaction_type_type']) {
                $name = $journal['source_account_name'];
            }

            $grouped[$name] = $grouped[$name] ?? '0';
            $grouped[$name] = bcadd($journal['amount'], $grouped[$name]);
        }

        return $grouped;
    }

    /**
     * Group transactions by tag.
     *
     * @param array $array
     *
     * @return array
     */
    protected function groupByTag(array $array): array // filter + group data
    {
        // group by category ID:
        $grouped = [];
        /** @var array $journal */
        foreach ($array as $journal) {
            $tags = $journal['tags'] ?? [];
            /**
             * @var int   $id
             * @var array $tag
             */
            foreach ($tags as $id => $tag) {
                $grouped[$id] = $grouped[$id] ?? '0';
                $grouped[$id] = bcadd($journal['amount'], $grouped[$id]);
            }
        }

        return $grouped;
    }

    /**
     * Spent by budget.
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     */
    protected function spentByBudget(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $total     = $assets->merge($opposing);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAccounts($total);
        $collector->withBudgetInformation();
        $journals = $collector->getExtractedJournals();
        $sum      = [];
        // loop to support multi currency
        foreach ($journals as $journal) {
            $currencyId = $journal['currency_id'];
            $budgetName = $journal['budget_name'];
            $budgetId   = (int)$journal['budget_id'];

            // if not set, set to zero:
            if (!isset($sum[$budgetId][$currencyId])) {
                $sum[$budgetId] = [
                    'grand_total'  => '0',
                    'name'         => $budgetName,
                    'per_currency' => [
                        $currencyId => [
                            'sum'      => '0',
                            'budget'   => [
                                'id'   => $budgetId,
                                'name' => $budgetName,
                            ],
                            'currency' => [
                                'symbol' => $journal['currency_symbol'],
                                'dp'     => $journal['currency_decimal_places'],
                            ],
                        ],
                    ],
                ];
            }

            // add amount
            $sum[$budgetId]['per_currency'][$currencyId]['sum'] = bcadd(
                $sum[$budgetId]['per_currency'][$currencyId]['sum'], $journal['amount']
            );
            $sum[$budgetId]['grand_total']                      = bcadd($sum[$budgetId]['grand_total'], $journal['amount']);
        }

        return $sum;
    }

    /**
     * Spent by category.
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     */
    protected function spentByCategory(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $total     = $assets->merge($opposing);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAccounts($total);
        $collector->withCategoryInformation();
        $journals = $collector->getExtractedJournals();
        $sum      = [];
        // loop to support multi currency
        foreach ($journals as $journal) {
            $currencyId   = (int)$journal['currency_id'];
            $categoryName = $journal['category_name'];
            $categoryId   = (int)$journal['category_id'];

            // if not set, set to zero:
            if (!isset($sum[$categoryId][$currencyId])) {
                $sum[$categoryId] = [
                    'grand_total'  => '0',
                    'name'         => $categoryName,
                    'per_currency' => [
                        $currencyId => [
                            'sum'      => '0',
                            'category' => [
                                'id'   => $categoryId,
                                'name' => $categoryName,
                            ],
                            'currency' => [
                                'symbol' => $journal['currency_symbol'],
                                'dp'     => $journal['currency_decimal_places'],
                            ],
                        ],
                    ],
                ];
            }

            // add amount
            $sum[$categoryId]['per_currency'][$currencyId]['sum'] = bcadd(
                $sum[$categoryId]['per_currency'][$currencyId]['sum'], $journal['amount']
            );
            $sum[$categoryId]['grand_total']                      = bcadd($sum[$categoryId]['grand_total'], $journal['amount']);
        }

        return $sum;
    }

    /**
     * Spent in a period.
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    protected function spentInPeriod(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $total = $assets->merge($opposing);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAccounts($total);
        $journals = $collector->getExtractedJournals();
        $sum      = [
            'grand_sum'    => '0',
            'per_currency' => [],
        ];
        // loop to support multi currency
        foreach ($journals as $journal) {
            $currencyId = (int)$journal['currency_id'];

            // if not set, set to zero:
            if (!isset($sum['per_currency'][$currencyId])) {
                $sum['per_currency'][$currencyId] = [
                    'sum'      => '0',
                    'currency' => [
                        'name'           => $journal['currency_name'],
                        'symbol'         => $journal['currency_symbol'],
                        'decimal_places' => $journal['currency_decimal_places'],
                    ],
                ];
            }

            // add amount
            $sum['per_currency'][$currencyId]['sum'] = bcadd($sum['per_currency'][$currencyId]['sum'], $journal['amount']);
            $sum['grand_sum']                        = bcadd($sum['grand_sum'], $journal['amount']);
        }

        return $sum;
    }

    /**
     *
     * Returns an array with the following values:
     * 0 =>
     *   'name' => name of budget + repetition
     *   'left' => left in budget repetition (always zero)
     *   'overspent' => spent more than budget repetition? (always zero)
     *   'spent' => actually spent in period for budget
     * 1 => (etc)
     *
     * @param Budget     $budget
     * @param Collection $limits
     *
     * @return array
     *
     */
    protected function spentInPeriodMulti(Budget $budget, Collection $limits): array // get data + augment with info
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository = app(OperationsRepositoryInterface::class);

        $return = [];
        $format = (string)trans('config.month_and_day');
        $name   = $budget->name;
        /** @var BudgetLimit $budgetLimit */
        foreach ($limits as $budgetLimit) {
            $expenses = $opsRepository->spentInPeriod(new Collection([$budget]), new Collection, $budgetLimit->start_date, $budgetLimit->end_date);
            $expenses = app('steam')->positive($expenses);

            if ($limits->count() > 1) {
                $name = $budget->name . ' ' . trans(
                        'firefly.between_dates',
                        [
                            'start' => $budgetLimit->start_date->formatLocalized($format),
                            'end'   => $budgetLimit->end_date->formatLocalized($format),
                        ]
                    );
            }
            $amount       = $budgetLimit->amount;
            $leftInLimit  = bcsub($amount, $expenses);
            $hasOverspent = bccomp($leftInLimit, '0') === -1;
            $left         = $hasOverspent ? '0' : bcsub($amount, $expenses);
            $spent        = $hasOverspent ? $amount : $expenses;
            $overspent    = $hasOverspent ? app('steam')->positive($leftInLimit) : '0';

            $return[$name] = [
                'left'      => $left,
                'overspent' => $overspent,
                'spent'     => $spent,
            ];
        }

        return $return;
    }

    /**
     * Returns an array with the following values:
     * 'name' => "no budget" in local language
     * 'repetition_left' => left in budget repetition (always zero)
     * 'repetition_overspent' => spent more than budget repetition? (always zero)
     * 'spent' => actually spent in period for budget.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    protected function spentInPeriodWithout(Carbon $start, Carbon $end): string // get data + augment with info
    {
        // collector
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $types     = [TransactionType::WITHDRAWAL];
        $collector->setTypes($types)->setRange($start, $end)->withoutBudget();

        return $collector->getSum();
    }
}
