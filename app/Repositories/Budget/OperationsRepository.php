<?php

/**
 * OperationsRepository.php
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Report\Summarizer\TransactionSummarizer;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class OperationsRepository
 */
class OperationsRepository implements OperationsRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    /**
     * A method that returns the amount of money budgeted per day for this budget,
     * on average.
     */
    public function budgetedPerDay(Budget $budget): string
    {
        app('log')->debug(sprintf('Now with budget #%d "%s"', $budget->id, $budget->name));
        $total = '0';
        $count = 0;
        foreach ($budget->budgetlimits as $limit) {
            $diff   = (int) $limit->start_date->diffInDays($limit->end_date, true);
            $diff   = 0 === $diff ? 1 : $diff;
            $amount = $limit->amount;
            $perDay = bcdiv((string) $amount, (string) $diff);
            $total  = bcadd($total, $perDay);
            ++$count;
            app('log')->debug(sprintf('Found %d budget limits. Per day is %s, total is %s', $count, $perDay, $total));
        }
        $avg   = $total;
        if ($count > 0) {
            $avg = bcdiv($total, (string) $count);
        }
        app('log')->debug(sprintf('%s / %d = %s = average.', $total, $count, $avg));

        return $avg;
    }

    /**
     * This method is being used to generate the budget overview in the year/multi-year report. Its used
     * in both the year/multi-year budget overview AND in the accompanying chart.
     *
     * @deprecated
     */
    public function getBudgetPeriodReport(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = app('navigation')->preferredCarbonFormat($start, $end);
        $data         = [];

        // get all transactions:
        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setBudgets($budgets);
        $journals     = $collector->getExtractedJournals();

        // loop transactions:
        /** @var array $journal */
        foreach ($journals as $journal) {
            // prep data array for currency:
            $budgetId                     = (int) $journal['budget_id'];
            $budgetName                   = $journal['budget_name'];
            $currencyId                   = (int) $journal['currency_id'];
            $key                          = sprintf('%d-%d', $budgetId, $currencyId);

            $data[$key] ??= [
                'id'                      => $budgetId,
                'name'                    => sprintf('%s (%s)', $budgetName, $journal['currency_name']),
                'sum'                     => '0',
                'currency_id'             => $currencyId,
                'currency_code'           => $journal['currency_code'],
                'currency_name'           => $journal['currency_name'],
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_decimal_places' => $journal['currency_decimal_places'],
                'entries'                 => [],
            ];
            $date                         = $journal['date']->format($carbonFormat);
            $data[$key]['entries'][$date] = bcadd($data[$key]['entries'][$date] ?? '0', (string) $journal['amount']);
        }

        return $data;
    }

    /**
     * This method returns a list of all the withdrawal transaction journals (as arrays) set in that period
     * which have the specified budget set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always negative.
     */
    public function listExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $budgets = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
        if ($accounts instanceof Collection && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if ($budgets instanceof Collection && $budgets->count() > 0) {
            $collector->setBudgets($budgets);
        }
        if (!$budgets instanceof Collection || 0 === $budgets->count()) {
            $collector->setBudgets($this->getBudgets());
        }
        $collector->withBudgetInformation()->withAccountInformation()->withCategoryInformation();
        $journals  = $collector->getExtractedJournals();
        $array     = [];

        foreach ($journals as $journal) {
            $currencyId                                                                   = (int) $journal['currency_id'];
            $budgetId                                                                     = (int) $journal['budget_id'];
            $budgetName                                                                   = (string) $journal['budget_name'];

            // catch "no category" entries.
            if (0 === $budgetId) {
                continue;
            }

            // info about the currency:
            $array[$currencyId]                       ??= [
                'budgets'                 => [],
                'currency_id'             => $currencyId,
                'currency_name'           => $journal['currency_name'],
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_code'           => $journal['currency_code'],
                'currency_decimal_places' => $journal['currency_decimal_places'],
            ];

            // info about the categories:
            $array[$currencyId]['budgets'][$budgetId] ??= [
                'id'                   => $budgetId,
                'name'                 => $budgetName,
                'transaction_journals' => [],
            ];

            // add journal to array:
            // only a subset of the fields.
            $journalId                                                                    = (int) $journal['transaction_journal_id'];
            $array[$currencyId]['budgets'][$budgetId]['transaction_journals'][$journalId] = [
                'amount'                   => app('steam')->negative($journal['amount']),
                'destination_account_id'   => $journal['destination_account_id'],
                'destination_account_name' => $journal['destination_account_name'],
                'source_account_id'        => $journal['source_account_id'],
                'source_account_name'      => $journal['source_account_name'],
                'category_name'            => $journal['category_name'],
                'description'              => $journal['description'],
                'transaction_group_id'     => $journal['transaction_group_id'],
                'date'                     => $journal['date'],
            ];
        }

        return $array;
    }

    private function getBudgets(): Collection
    {
        /** @var BudgetRepositoryInterface $repos */
        $repos = app(BudgetRepositoryInterface::class);

        return $repos->getActiveBudgets();
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function sumExpenses(
        Carbon               $start,
        Carbon               $end,
        ?Collection          $accounts = null,
        ?Collection          $budgets = null,
        ?TransactionCurrency $currency = null,
        bool                 $convertToNative = false
    ): array {
        Log::debug(sprintf('Start of %s(date, date, array, array, "%s", "%s").', __METHOD__, $currency?->code, var_export($convertToNative, true)));
        // this collector excludes all transfers TO liabilities (which are also withdrawals)
        // because those expenses only become expenses once they move from the liability to the friend.
        // 2024-12-24 disable the exclusion for now.

        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($this->user);
        $subset     = $repository->getAccountsByType(config('firefly.valid_liabilities'));
        $selection  = new Collection();

        /** @var Account $account */
        foreach ($subset as $account) {
            if ('credit' === $repository->getMetaValue($account, 'liability_direction')) {
                $selection->push($account);
            }
        }

        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)
            ->setRange($start, $end)
            // ->excludeDestinationAccounts($selection)
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value])
        ;

        if ($accounts instanceof Collection) {
            $collector->setAccounts($accounts);
        }
        if (!$budgets instanceof Collection) {
            $budgets = $this->getBudgets();
        }
        if ($currency instanceof TransactionCurrency) {
            Log::debug(sprintf('Limit to normal currency %s', $currency->code));
            $collector->setNormalCurrency($currency);
        }
        if ($budgets->count() > 0) {
            $collector->setBudgets($budgets);
        }
        $journals   = $collector->getExtractedJournals();

        // same but for transactions in the foreign currency:
        if ($currency instanceof TransactionCurrency) {
            Log::debug('STOP looking for transactions in the foreign currency.');
        }
        $summarizer = new TransactionSummarizer($this->user);
        // 2025-04-21 overrule "convertToNative" because in this particular view, we never want to do this.
        $summarizer->setConvertToNative($convertToNative);

        return $summarizer->groupByCurrencyId($journals, 'negative', false);
    }
}
