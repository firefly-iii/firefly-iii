<?php

/**
 * SummaryController.php
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

namespace FireflyIII\Api\V1\Controllers;


use Carbon\Carbon;
use Exception;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class SummaryController
 */
class SummaryController extends Controller
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var BillRepositoryInterface */
    private $billRepository;
    /** @var BudgetRepositoryInterface */
    private $budgetRepository;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepos;

    /**
     * SummaryController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                    = auth()->user();
                $this->currencyRepos     = app(CurrencyRepositoryInterface::class);
                $this->billRepository    = app(BillRepositoryInterface::class);
                $this->budgetRepository  = app(BudgetRepositoryInterface::class);
                $this->accountRepository = app(AccountRepositoryInterface::class);

                $this->billRepository->setUser($user);
                $this->currencyRepos->setUser($user);
                $this->budgetRepository->setUser($user);
                $this->accountRepository->setUser($user);


                return $next($request);
            }
        );
    }

    /**
     * @param DateRequest $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function basic(DateRequest $request): JsonResponse
    {
        // parameters for boxes:
        $dates = $request->getAll();
        $start = $dates['start'];
        $end   = $dates['end'];
        $code  = $request->get('currency_code');

        // balance information:
        $balanceData  = $this->getBalanceInformation($start, $end);
        $billData     = $this->getBillInformation($start, $end);
        $spentData    = $this->getLeftToSpendInfo($start, $end);
        $networthData = $this->getNetWorthInfo($start, $end);
        $total        = array_merge($balanceData, $billData, $spentData, $networthData);

        // give new keys
        $return = [];
        foreach ($total as $entry) {
            if (null === $code || (null !== $code && $code === $entry['currency_code'])) {
                $return[$entry['key']] = $entry;
            }
        }

        return response()->json($return);

    }

    /**
     * Check if date is outside session range.
     *
     * @param Carbon $date
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return bool
     */
    protected function notInDateRange(Carbon $date, Carbon $start, Carbon $end): bool // Validate a preference
    {
        $result = false;
        if ($start->greaterThanOrEqualTo($date) && $end->greaterThanOrEqualTo($date)) {
            $result = true;
        }
        // start and end in the past? use $end
        if ($start->lessThanOrEqualTo($date) && $end->lessThanOrEqualTo($date)) {
            $result = true;
        }

        return $result;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    private function getBalanceInformation(Carbon $start, Carbon $end): array
    {
        // prep some arrays:
        $incomes  = [];
        $expenses = [];
        $sums     = [];
        $return   = [];

        // collect income of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setRange($start, $end)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes([TransactionType::DEPOSIT]);

        $set = $collector->getExtractedJournals();
        /** @var array $transactionJournal */
        foreach ($set as $transactionJournal) {

            $currencyId           = (int)$transactionJournal['currency_id'];
            $incomes[$currencyId] = $incomes[$currencyId] ?? '0';
            $incomes[$currencyId] = bcadd($incomes[$currencyId], bcmul($transactionJournal['amount'], '-1'));
            $sums[$currencyId]    = $sums[$currencyId] ?? '0';
            $sums[$currencyId]    = bcadd($sums[$currencyId], bcmul($transactionJournal['amount'], '-1'));
        }

        // collect expenses of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setRange($start, $end)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes([TransactionType::WITHDRAWAL]);


        $set = $collector->getExtractedJournals();

        /** @var array $transactionJournal */
        foreach ($set as $transactionJournal) {
            $currencyId            = (int)$transactionJournal['currency_id'];
            $expenses[$currencyId] = $expenses[$currencyId] ?? '0';
            $expenses[$currencyId] = bcadd($expenses[$currencyId], $transactionJournal['amount']);
            $sums[$currencyId]     = $sums[$currencyId] ?? '0';
            $sums[$currencyId]     = bcadd($sums[$currencyId], $transactionJournal['amount']);
        }

        // format amounts:
        $keys = array_keys($sums);
        foreach ($keys as $currencyId) {
            $currency = $this->currencyRepos->findNull($currencyId);
            if (null === $currency) {
                continue;
            }
            // create objects for big array.
            $return[] = [
                'key'                     => sprintf('balance-in-%s', $currency->code),
                'title'                   => trans('firefly.box_balance_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => round($sums[$currencyId] ?? 0, $currency->decimal_places),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => app('amount')->formatAnything($currency, $sums[$currencyId] ?? '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => app('amount')->formatAnything($currency, $expenses[$currencyId] ?? '0', false) .
                                             ' + ' . app('amount')->formatAnything($currency, $incomes[$currencyId] ?? '0', false),
            ];
            $return[] = [
                'key'                     => sprintf('spent-in-%s', $currency->code),
                'title'                   => trans('firefly.box_spent_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => round($expenses[$currencyId] ?? 0, $currency->decimal_places),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => app('amount')->formatAnything($currency, $expenses[$currencyId] ?? '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => '',
            ];
            $return[] = [
                'key'                     => sprintf('earned-in-%s', $currency->code),
                'title'                   => trans('firefly.box_earned_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => round($incomes[$currencyId] ?? 0, $currency->decimal_places),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => app('amount')->formatAnything($currency, $incomes[$currencyId] ?? '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => '',
            ];
        }

        return $return;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    private function getBillInformation(Carbon $start, Carbon $end): array
    {
        /*
         * Since both this method and the chart use the exact same data, we can suffice
         * with calling the one method in the bill repository that will get this amount.
         */
        $paidAmount   = $this->billRepository->getBillsPaidInRangePerCurrency($start, $end);
        $unpaidAmount = $this->billRepository->getBillsUnpaidInRangePerCurrency($start, $end);
        $return       = [];
        foreach ($paidAmount as $currencyId => $amount) {
            $amount   = bcmul($amount, '-1');
            $currency = $this->currencyRepos->findNull((int)$currencyId);
            if (null === $currency) {
                continue;
            }
            $return[] = [
                'key'                     => sprintf('bills-paid-in-%s', $currency->code),
                'title'                   => trans('firefly.box_bill_paid_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => round($amount, $currency->decimal_places),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => app('amount')->formatAnything($currency, $amount, false),
                'local_icon'              => 'check',
                'sub_title'               => '',
            ];
        }

        foreach ($unpaidAmount as $currencyId => $amount) {
            $amount   = bcmul($amount, '-1');
            $currency = $this->currencyRepos->findNull((int)$currencyId);
            if (null === $currency) {
                continue;
            }
            $return[] = [
                'key'                     => sprintf('bills-unpaid-in-%s', $currency->code),
                'title'                   => trans('firefly.box_bill_unpaid_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => round($amount, $currency->decimal_places),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => app('amount')->formatAnything($currency, $amount, false),
                'local_icon'              => 'calendar-o',
                'sub_title'               => '',
            ];
        }

        return $return;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     * @throws Exception
     */
    private function getLeftToSpendInfo(Carbon $start, Carbon $end): array
    {
        $return    = [];
        $today     = new Carbon;
        $available = $this->budgetRepository->getAvailableBudgetWithCurrency($start, $end);
        $budgets   = $this->budgetRepository->getActiveBudgets();
        $spentInfo = $this->budgetRepository->spentInPeriodMc($budgets, new Collection, $start, $end);
        foreach ($available as $currencyId => $amount) {
            $currency = $this->currencyRepos->findNull($currencyId);
            if (null === $currency) {
                continue;
            }
            $spentInCurrency = (string)$this->findInSpentArray($spentInfo, $currency);
            $leftToSpend     = bcadd($amount, $spentInCurrency);

            $days   = $today->diffInDays($end) + 1;
            $perDay = '0';
            if (0 !== $days && bccomp($leftToSpend, '0') > -1) {
                $perDay = bcdiv($leftToSpend, (string)$days);
            }

            $return[] = [
                'key'                     => sprintf('left-to-spend-in-%s', $currency->code),
                'title'                   => trans('firefly.box_left_to_spend_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => round($leftToSpend, $currency->decimal_places),
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => app('amount')->formatAnything($currency, $leftToSpend, false),
                'local_icon'              => 'money',
                'sub_title'               => (string)trans('firefly.box_spend_per_day', ['amount' => app('amount')->formatAnything($currency, $perDay, false)]),
            ];
        }

        return $return;
    }

    /**
     * This method will scroll through the results of the spentInPeriodMc() array and return the correct info.
     *
     * @param array $spentInfo
     * @param TransactionCurrency $currency
     *
     * @return float
     */
    private function findInSpentArray(array $spentInfo, TransactionCurrency $currency): float
    {
        foreach ($spentInfo as $array) {
            if ($array['currency_id'] === $currency->id) {
                return $array['amount'];
            }
        }

        return 0.0; // @codeCoverageIgnore
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    private function getNetWorthInfo(Carbon $start, Carbon $end): array
    {
        /** @var User $user */
        $user = auth()->user();
        $date = Carbon::now()->startOfDay();


        // start and end in the future? use $end
        if ($this->notInDateRange($date, $start, $end)) {
            /** @var Carbon $date */
            $date = session('end', Carbon::now()->endOfMonth());
        }

        /** @var NetWorthInterface $netWorthHelper */
        $netWorthHelper = app(NetWorthInterface::class);
        $netWorthHelper->setUser($user);
        $allAccounts = $this->accountRepository->getActiveAccountsByType([AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE]);

        // filter list on preference of being included.
        $filtered = $allAccounts->filter(
            function (Account $account) {
                $includeNetWorth = $this->accountRepository->getMetaValue($account, 'include_net_worth');

                return null === $includeNetWorth ? true : '1' === $includeNetWorth;
            }
        );

        $netWorthSet = $netWorthHelper->getNetWorthByCurrency($filtered, $date);
        $return      = [];
        foreach ($netWorthSet as $data) {
            /** @var TransactionCurrency $currency */
            $currency = $data['currency'];
            $amount   = round($data['balance'], $currency->decimal_places);
            if (0.0 === $amount) {
                continue;
            }
            // return stuff
            $return[] = [
                'key'                     => sprintf('net-worth-in-%s', $currency->code),
                'title'                   => trans('firefly.box_net_worth_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => $amount,
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => app('amount')->formatAnything($currency, $data['balance'], false),
                'local_icon'              => 'line-chart',
                'sub_title'               => '',
            ];
        }

        return $return;
    }

}
