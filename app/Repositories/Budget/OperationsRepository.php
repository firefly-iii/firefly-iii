<?php
/**
 * OperationsRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class OperationsRepository
 */
class OperationsRepository implements OperationsRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
            die(get_class($this));
        }
    }

    /**
     * A method that returns the amount of money budgeted per day for this budget,
     * on average.
     *
     * @param Budget $budget
     *
     * @return string
     */
    public function budgetedPerDay(Budget $budget): string
    {
        Log::debug(sprintf('Now with budget #%d "%s"', $budget->id, $budget->name));
        $total = '0';
        $count = 0;
        foreach ($budget->budgetlimits as $limit) {
            $diff   = $limit->start_date->diffInDays($limit->end_date);
            $diff   = 0 === $diff ? 1 : $diff;
            $amount = (string)$limit->amount;
            $perDay = bcdiv($amount, (string)$diff);
            $total  = bcadd($total, $perDay);
            $count++;
            Log::debug(sprintf('Found %d budget limits. Per day is %s, total is %s', $count, $perDay, $total));
        }
        $avg = $total;
        if ($count > 0) {
            $avg = bcdiv($total, (string)$count);
        }
        Log::debug(sprintf('%s / %d = %s = average.', $total, $count, $avg));

        return $avg;
    }

    /**
     * This method collects various info on budgets, used on the budget page and on the index.
     *
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     *
     */
    public function collectBudgetInformation(Collection $budgets, Carbon $start, Carbon $end): array
    {
        // get account information
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $return            = [];
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $budgetId          = $budget->id;
            $return[$budgetId] = [
                'spent'    => $this->spentInPeriod(new Collection([$budget]), $accounts, $start, $end),
                'budgeted' => '0',
            ];
            $budgetLimits      = $this->getBudgetLimits($budget, $start, $end);
            $otherLimits       = new Collection;

            // get all the budget limits relevant between start and end and examine them:
            /** @var BudgetLimit $limit */
            foreach ($budgetLimits as $limit) {
                if ($limit->start_date->isSameDay($start) && $limit->end_date->isSameDay($end)
                ) {
                    $return[$budgetId]['currentLimit'] = $limit;
                    $return[$budgetId]['budgeted']     = round($limit->amount, $defaultCurrency->decimal_places);
                    continue;
                }
                // otherwise it's just one of the many relevant repetitions:
                $otherLimits->push($limit);
            }
            $return[$budgetId]['otherLimits'] = $otherLimits;
        }

        return $return;
    }

    /**
     * This method is being used to generate the budget overview in the year/multi-year report. Its used
     * in both the year/multi-year budget overview AND in the accompanying chart.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function getBudgetPeriodReport(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = app('navigation')->preferredCarbonFormat($start, $end);
        $data         = [];


        // get all transactions:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setBudgets($budgets);
        $journals = $collector->getExtractedJournals();

        // loop transactions:
        /** @var array $journal */
        foreach ($journals as $journal) {
            // prep data array for currency:
            $budgetId   = (int)$journal['budget_id'];
            $budgetName = $journal['budget_name'];
            $currencyId = (int)$journal['currency_id'];
            $key        = sprintf('%d-%d', $budgetId, $currencyId);

            $data[$key]                   = $data[$key] ?? [
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
            $data[$key]['entries'][$date] = bcadd($data[$budgetId]['entries'][$date] ?? '0', $journal['amount']);
        }

        return $data;
    }

    /**
     * This method returns a list of all the withdrawal transaction journals (as arrays) set in that period
     * which have the specified budget set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $budgets
     *
     * @return array
     */
    public function listExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $budgets = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $budgets && $budgets->count() > 0) {
            $collector->setBudgets($budgets);
        }
        if (null === $budgets || (null !== $budgets && 0 === $budgets->count())) {
            $collector->setBudgets($this->getBudgets());
        }
        $collector->withBudgetInformation()->withAccountInformation()->withCategoryInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId = (int)$journal['currency_id'];
            $budgetId   = (int)$journal['budget_id'];
            $budgetName = (string)$journal['budget_name'];

            // catch "no category" entries.
            if (0 === $budgetId) {
                continue;
            }

            // info about the currency:
            $array[$currencyId] = $array[$currencyId] ?? [
                    'budgets'                 => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];

            // info about the categories:
            $array[$currencyId]['budgets'][$budgetId] = $array[$currencyId]['budgets'][$budgetId] ?? [
                    'id'                   => $budgetId,
                    'name'                 => $budgetName,
                    'transaction_journals' => [],
                ];

            // add journal to array:
            // only a subset of the fields.
            $journalId = (int)$journal['transaction_journal_id'];


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

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     * @deprecated
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setBudgets($budgets)->withBudgetInformation();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }

        return $collector->getSum();
    }

    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @deprecated
     */
    public function spentInPeriodMc(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setBudgets($budgets)->withBudgetInformation();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        // TODO possible candidate for getExtractedGroups
        $set        = $collector->getGroups();
        $return     = [];
        $total      = [];
        $currencies = [];
        /** @var array $group */
        foreach ($set as $group) {
            /** @var array $transaction */
            foreach ($group['transactions'] as $transaction) {
                $code = $transaction['currency_code'];
                if (!isset($currencies[$code])) {
                    $currencies[$code] = [
                        'id'             => $transaction['currency_id'],
                        'decimal_places' => $transaction['currency_decimal_places'],
                        'code'           => $transaction['currency_code'],
                        'name'           => $transaction['currency_name'],
                        'symbol'         => $transaction['currency_symbol'],
                    ];
                }
                $total[$code] = isset($total[$code]) ? bcadd($total[$code], $transaction['amount']) : $transaction['amount'];
            }
        }
        /**
         * @var string $code
         * @var string $spent
         */
        foreach ($total as $code => $spent) {
            /** @var TransactionCurrency $currency */
            $currency = $currencies[$code];
            $return[] = [
                'currency_id'             => $currency['id'],
                'currency_code'           => $code,
                'currency_name'           => $currency['name'],
                'currency_symbol'         => $currency['symbol'],
                'currency_decimal_places' => $currency['decimal_places'],
                'amount'                  => $spent,
            ];
        }

        return $return;
    }

    /**
     * @param Carbon                   $start
     * @param Carbon                   $end
     * @param Collection|null          $accounts
     * @param Collection|null          $budgets
     * @param TransactionCurrency|null $currency
     *
     * @return array
     */
    public function sumExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $budgets = null, ?TransactionCurrency $currency = null
    ): array {

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null === $budgets || (null !== $budgets && 0 === $budgets->count())) {
            $budgets = $this->getBudgets();
        }
        if (null !== $currency) {
            $collector->setCurrency($currency);
        }
        $collector->setBudgets($budgets);
        $collector->withBudgetInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId                = (int)$journal['currency_id'];
            $array[$currencyId]        = $array[$currencyId] ?? [
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->negative($journal['amount']));
        }

        return $array;
    }

    /**
     * For now, simply refer to whichever repository holds this function.
     * TODO might be done better in the future.
     *
     * @param Budget      $budget
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return Collection
     */
    private function getBudgetLimits(Budget $budget, Carbon $start = null, Carbon $end = null): Collection
    {
        /** @var BudgetLimitRepositoryInterface $blRepository */
        $blRepository = app(BudgetLimitRepositoryInterface::class);

        return $blRepository->getBudgetLimits($budget, $start, $end);
    }

    /**
     * @return Collection
     */
    private function getBudgets(): Collection
    {
        /** @var BudgetRepositoryInterface $repos */
        $repos = app(BudgetRepositoryInterface::class);

        return $repos->getActiveBudgets();
    }
}