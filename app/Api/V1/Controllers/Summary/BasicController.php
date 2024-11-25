<?php

/*
 * SummaryController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Summary;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\DateRequest;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class BasicController
 */
class BasicController extends Controller
{
    private AvailableBudgetRepositoryInterface $abRepository;
    private AccountRepositoryInterface         $accountRepository;
    private BillRepositoryInterface            $billRepository;
    private BudgetRepositoryInterface          $budgetRepository;
    private CurrencyRepositoryInterface        $currencyRepos;
    private OperationsRepositoryInterface      $opsRepository;

    /**
     * BasicController constructor.
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
                $this->abRepository      = app(AvailableBudgetRepositoryInterface::class);
                $this->opsRepository     = app(OperationsRepositoryInterface::class);

                $this->billRepository->setUser($user);
                $this->currencyRepos->setUser($user);
                $this->budgetRepository->setUser($user);
                $this->accountRepository->setUser($user);
                $this->abRepository->setUser($user);
                $this->opsRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/summary/getBasicSummary
     *
     * @throws \Exception
     */
    public function basic(DateRequest $request): JsonResponse
    {
        // parameters for boxes:
        $dates        = $request->getAll();
        $start        = $dates['start'];
        $end          = $dates['end'];
        $code         = $request->get('currency_code');

        // balance information:
        $balanceData  = $this->getBalanceInformation($start, $end);
        $billData     = $this->getBillInformation($start, $end);
        $spentData    = $this->getLeftToSpendInfo($start, $end);
        $netWorthData = $this->getNetWorthInfo($start, $end);
        //        $balanceData  = [];
        //        $billData     = [];
        //        $spentData    = [];
        //        $netWorthData = [];
        $total        = array_merge($balanceData, $billData, $spentData, $netWorthData);

        // give new keys
        $return       = [];
        foreach ($total as $entry) {
            if (null === $code || ($code === $entry['currency_code'])) {
                $return[$entry['key']] = $entry;
            }
        }

        return response()->json($return);
    }

    private function getBalanceInformation(Carbon $start, Carbon $end): array
    {
        // prep some arrays:
        $incomes   = [];
        $expenses  = [];
        $sums      = [];
        $return    = [];

        // collect income of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setPage($this->parameters->get('page'))->setTypes([TransactionTypeEnum::DEPOSIT->value]);

        $set       = $collector->getExtractedJournals();

        /** @var array $transactionJournal */
        foreach ($set as $transactionJournal) {
            $currencyId           = (int)$transactionJournal['currency_id'];
            $incomes[$currencyId] ??= '0';
            $incomes[$currencyId] = bcadd(
                $incomes[$currencyId],
                bcmul($transactionJournal['amount'], '-1')
            );
            $sums[$currencyId]    ??= '0';
            $sums[$currencyId]    = bcadd($sums[$currencyId], bcmul($transactionJournal['amount'], '-1'));
        }

        // collect expenses of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setRange($start, $end)->setPage($this->parameters->get('page'))->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
        $set       = $collector->getExtractedJournals();

        /** @var array $transactionJournal */
        foreach ($set as $transactionJournal) {
            $currencyId            = (int)$transactionJournal['currency_id'];
            $expenses[$currencyId] ??= '0';
            $expenses[$currencyId] = bcadd($expenses[$currencyId], $transactionJournal['amount']);
            $sums[$currencyId]     ??= '0';
            $sums[$currencyId]     = bcadd($sums[$currencyId], $transactionJournal['amount']);
        }

        // format amounts:
        $keys      = array_keys($sums);
        foreach ($keys as $currencyId) {
            $currency = $this->currencyRepos->find($currencyId);
            if (null === $currency) {
                continue;
            }
            // create objects for big array.
            $return[] = [
                'key'                     => sprintf('balance-in-%s', $currency->code),
                'title'                   => trans('firefly.box_balance_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => $sums[$currencyId] ?? '0',
                'currency_id'             => (string)$currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => app('amount')->formatAnything($currency, $sums[$currencyId] ?? '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => app('amount')->formatAnything($currency, $expenses[$currencyId] ?? '0', false).
                                             ' + '.app('amount')->formatAnything($currency, $incomes[$currencyId] ?? '0', false),
            ];
            $return[] = [
                'key'                     => sprintf('spent-in-%s', $currency->code),
                'title'                   => trans('firefly.box_spent_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => $expenses[$currencyId] ?? '0',
                'currency_id'             => (string)$currency->id,
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
                'monetary_value'          => $incomes[$currencyId] ?? '0',
                'currency_id'             => (string)$currency->id,
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

    private function getBillInformation(Carbon $start, Carbon $end): array
    {
        app('log')->debug(sprintf('Now in getBillInformation("%s", "%s")', $start->format('Y-m-d'), $end->format('Y-m-d-')));
        /*
         * Since both this method and the chart use the exact same data, we can suffice
         * with calling the one method in the bill repository that will get this amount.
         */
        $paidAmount   = $this->billRepository->sumPaidInRange($start, $end);
        $unpaidAmount = $this->billRepository->sumUnpaidInRange($start, $end);

        $return       = [];

        /**
         * @var array $info
         */
        foreach ($paidAmount as $info) {
            $amount   = bcmul($info['sum'], '-1');
            $return[] = [
                'key'                     => sprintf('bills-paid-in-%s', $info['code']),
                'title'                   => trans('firefly.box_bill_paid_in_currency', ['currency' => $info['symbol']]),
                'monetary_value'          => $amount,
                'currency_id'             => (string)$info['id'],
                'currency_code'           => $info['code'],
                'currency_symbol'         => $info['symbol'],
                'currency_decimal_places' => $info['decimal_places'],
                'value_parsed'            => app('amount')->formatFlat($info['symbol'], $info['decimal_places'], $amount, false),
                'local_icon'              => 'check',
                'sub_title'               => '',
            ];
        }

        /**
         * @var array $info
         */
        foreach ($unpaidAmount as $info) {
            $amount   = bcmul($info['sum'], '-1');
            $return[] = [
                'key'                     => sprintf('bills-unpaid-in-%s', $info['code']),
                'title'                   => trans('firefly.box_bill_unpaid_in_currency', ['currency' => $info['symbol']]),
                'monetary_value'          => $amount,
                'currency_id'             => (string)$info['id'],
                'currency_code'           => $info['code'],
                'currency_symbol'         => $info['symbol'],
                'currency_decimal_places' => $info['decimal_places'],
                'value_parsed'            => app('amount')->formatFlat($info['symbol'], $info['decimal_places'], $amount, false),
                'local_icon'              => 'calendar-o',
                'sub_title'               => '',
            ];
        }
        app('log')->debug(sprintf('Done with getBillInformation("%s", "%s")', $start->format('Y-m-d'), $end->format('Y-m-d-')));

        return $return;
    }

    /**
     * @throws \Exception
     */
    private function getLeftToSpendInfo(Carbon $start, Carbon $end): array
    {
        $return    = [];
        $today     = today(config('app.timezone'));
        $available = $this->abRepository->getAvailableBudgetWithCurrency($start, $end);
        $budgets   = $this->budgetRepository->getActiveBudgets();
        $spent     = $this->opsRepository->sumExpenses($start, $end, null, $budgets);

        foreach ($spent as $row) {
            // either an amount was budgeted or 0 is available.
            $amount          = (string)($available[$row['currency_id']] ?? '0');
            $spentInCurrency = $row['sum'];
            $leftToSpend     = bcadd($amount, $spentInCurrency);

            $days            = (int)$today->diffInDays($end, true) + 1;
            $perDay          = '0';
            if (0 !== $days && bccomp($leftToSpend, '0') > -1) {
                $perDay = bcdiv($leftToSpend, (string)$days);
            }

            $return[]        = [
                'key'                     => sprintf('left-to-spend-in-%s', $row['currency_code']),
                'title'                   => trans('firefly.box_left_to_spend_in_currency', ['currency' => $row['currency_symbol']]),
                'monetary_value'          => $leftToSpend,
                'currency_id'             => (string)$row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_symbol'         => $row['currency_symbol'],
                'currency_decimal_places' => $row['currency_decimal_places'],
                'value_parsed'            => app('amount')->formatFlat($row['currency_symbol'], $row['currency_decimal_places'], $leftToSpend, false),
                'local_icon'              => 'money',
                'sub_title'               => app('amount')->formatFlat(
                    $row['currency_symbol'],
                    $row['currency_decimal_places'],
                    $perDay,
                    false
                ),
            ];
        }

        return $return;
    }

    private function getNetWorthInfo(Carbon $start, Carbon $end): array
    {
        /** @var User $user */
        $user           = auth()->user();
        $date           = today(config('app.timezone'))->startOfDay();
        // start and end in the future? use $end
        if ($this->notInDateRange($date, $start, $end)) {
            /** @var Carbon $date */
            $date = session('end', today(config('app.timezone'))->endOfMonth());
        }

        /** @var NetWorthInterface $netWorthHelper */
        $netWorthHelper = app(NetWorthInterface::class);
        $netWorthHelper->setUser($user);
        $allAccounts    = $this->accountRepository->getActiveAccountsByType(
            [AccountType::ASSET, AccountType::DEFAULT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::DEBT]
        );

        // filter list on preference of being included.
        $filtered       = $allAccounts->filter(
            function (Account $account) {
                $includeNetWorth = $this->accountRepository->getMetaValue($account, 'include_net_worth');

                return null === $includeNetWorth || '1' === $includeNetWorth;
            }
        );

        $netWorthSet    = $netWorthHelper->byAccounts($filtered, $date);
        $return         = [];
        foreach ($netWorthSet as $key => $data) {
            if ('native' === $key) {
                continue;
            }
            $amount   = $data['balance'];
            if (0 === bccomp($amount, '0')) {
                continue;
            }
            // return stuff
            $return[] = [
                'key'                     => sprintf('net-worth-in-%s', $data['currency_code']),
                'title'                   => trans('firefly.box_net_worth_in_currency', ['currency' => $data['currency_symbol']]),
                'monetary_value'          => $amount,
                'currency_id'             => (string)$data['currency_id'],
                'currency_code'           => $data['currency_code'],
                'currency_symbol'         => $data['currency_symbol'],
                'currency_decimal_places' => $data['currency_decimal_places'],
                'value_parsed'            => app('amount')->formatFlat($data['currency_symbol'], $data['currency_decimal_places'], $data['balance'], false),
                'local_icon'              => 'line-chart',
                'sub_title'               => '',
            ];
        }

        return $return;
    }

    /**
     * Check if date is outside session range.
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
}
