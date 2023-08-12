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

namespace FireflyIII\Api\V2\Controllers\Summary;

use Carbon\Carbon;
use Exception;
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\DateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Administration\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Administration\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Administration\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Administration\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Administration\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
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
     *

     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                    = auth()->user();
                $this->abRepository      = app(AvailableBudgetRepositoryInterface::class);
                $this->accountRepository = app(AccountRepositoryInterface::class);
                $this->billRepository    = app(BillRepositoryInterface::class);
                $this->budgetRepository  = app(BudgetRepositoryInterface::class);
                $this->currencyRepos     = app(CurrencyRepositoryInterface::class);
                $this->opsRepository     = app(OperationsRepositoryInterface::class);

                $this->abRepository->setAdministrationId($user->user_group_id);
                $this->accountRepository->setAdministrationId($user->user_group_id);
                $this->billRepository->setAdministrationId($user->user_group_id);
                $this->budgetRepository->setAdministrationId($user->user_group_id);
                $this->currencyRepos->setUser($user);
                $this->opsRepository->setAdministrationId($user->user_group_id);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v2)#/summary/getBasicSummary
     *
     * @param DateRequest $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function basic(DateRequest $request): JsonResponse
    {
        // parameters for boxes:
        $start = $this->parameters->get('start');
        $end   = $this->parameters->get('end');

        // balance information:
        $balanceData  = [];
        $billData     = [];
        $spentData    = [];
        $netWorthData = [];
        $balanceData  = $this->getBalanceInformation($start, $end);
        $billData     = $this->getBillInformation($start, $end);
        $spentData    = $this->getLeftToSpendInfo($start, $end);
        $netWorthData = $this->getNetWorthInfo($start, $end);
        $total        = array_merge($balanceData, $billData, $spentData, $netWorthData);
        return response()->json($total);
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     * @throws FireflyException
     */
    private function getBalanceInformation(Carbon $start, Carbon $end): array
    {
        // prep some arrays:
        $incomes    = [];
        $expenses   = [];
        $sums       = [];
        $return     = [];
        $currencies = [];
        $converter  = new ExchangeRateConverter();
        $default    = app('amount')->getDefaultCurrency();
        /** @var User $user */
        $user = auth()->user();

        // collect income of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setRange($start, $end)
            ->setUserGroup($user->userGroup)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes([TransactionType::DEPOSIT])
            ->setRange($start, $end);

        $set = $collector->getExtractedJournals();
        /** @var array $transactionJournal */
        foreach ($set as $transactionJournal) {
            // transaction info:
            $currencyId              = (int)$transactionJournal['currency_id'];
            $amount                  = bcmul($transactionJournal['amount'], '-1');
            $currency                = $currencies[$currencyId] ?? TransactionCurrency::find($currencyId);
            $currencies[$currencyId] = $currency;
            $nativeAmount            = $converter->convert($currency, $default, $transactionJournal['date'], $amount);
            if ((int)$transactionJournal['foreign_currency_id'] === (int)$default->id) {
                // use foreign amount instead
                $nativeAmount = $transactionJournal['foreign_amount'];
            }
            // prep the arrays
            $incomes[$currencyId] = $incomes[$currencyId] ?? '0';
            $incomes['native']    = $incomes['native'] ?? '0';
            $sums[$currencyId]    = $sums[$currencyId] ?? '0';
            $sums['native']       = $sums['native'] ?? '0';

            // add values:
            $incomes[$currencyId] = bcadd($incomes[$currencyId], $amount);
            $sums[$currencyId]    = bcadd($sums[$currencyId], $amount);
            $incomes['native']    = bcadd($incomes['native'], $nativeAmount);
            $sums['native']       = bcadd($sums['native'], $nativeAmount);
        }

        // collect expenses of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setRange($start, $end)
            ->setUserGroup($user->userGroup)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes([TransactionType::WITHDRAWAL])
            ->setRange($start, $end);
        $set = $collector->getExtractedJournals();

        /** @var array $transactionJournal */
        foreach ($set as $transactionJournal) {
            // transaction info
            $currencyId              = (int)$transactionJournal['currency_id'];
            $amount                  = $transactionJournal['amount'];
            $currency                = $currencies[$currencyId] ?? $this->currencyRepos->find($currencyId);
            $currencies[$currencyId] = $currency;
            $nativeAmount            = $converter->convert($currency, $default, $transactionJournal['date'], $amount);
            if ((int)$transactionJournal['foreign_currency_id'] === (int)$default->id) {
                // use foreign amount instead
                $nativeAmount = $transactionJournal['foreign_amount'];
            }

            // prep arrays
            $expenses[$currencyId] = $expenses[$currencyId] ?? '0';
            $expenses['native']    = $expenses['native'] ?? '0';
            $sums[$currencyId]     = $sums[$currencyId] ?? '0';
            $sums['native']        = $sums['native'] ?? '0';

            // add values
            $expenses[$currencyId] = bcadd($expenses[$currencyId], $amount);
            $sums[$currencyId]     = bcadd($sums[$currencyId], $amount);
            $expenses['native']    = bcadd($expenses['native'], $nativeAmount);
            $sums['native']        = bcadd($sums['native'], $nativeAmount);
        }

        // create special array for native currency:
        $return[] = [
            'key'                     => 'balance-in-native',
            'value'                   => $sums['native'],
            'currency_id'             => $default->id,
            'currency_code'           => $default->code,
            'currency_symbol'         => $default->symbol,
            'currency_decimal_places' => $default->decimal_places,
        ];
        $return[] = [
            'key'                     => 'spent-in-native',
            'value'                   => $expenses['native'],
            'currency_id'             => $default->id,
            'currency_code'           => $default->code,
            'currency_symbol'         => $default->symbol,
            'currency_decimal_places' => $default->decimal_places,
        ];
        $return[] = [
            'key'                     => 'earned-in-native',
            'value'                   => $incomes['native'],
            'currency_id'             => $default->id,
            'currency_code'           => $default->code,
            'currency_symbol'         => $default->symbol,
            'currency_decimal_places' => $default->decimal_places,
        ];

        // format amounts:
        $keys = array_keys($sums);
        foreach ($keys as $currencyId) {
            if ('native' === $currencyId) {
                // skip native entries.
                continue;
            }
            $currency                = $currencies[$currencyId] ?? $this->currencyRepos->find($currencyId);
            $currencies[$currencyId] = $currency;
            // create objects for big array.
            $return[] = [
                'key'                     => sprintf('balance-in-%s', $currency->code),
                'value'                   => $sums[$currencyId] ?? '0',
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
            ];
            $return[] = [
                'key'                     => sprintf('spent-in-%s', $currency->code),
                'value'                   => $expenses[$currencyId] ?? '0',
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
            ];
            $return[] = [
                'key'                     => sprintf('earned-in-%s', $currency->code),
                'value'                   => $incomes[$currencyId] ?? '0',
                'currency_id'             => $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
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
        $paidAmount   = $this->billRepository->sumPaidInRange($start, $end);
        $unpaidAmount = $this->billRepository->sumUnpaidInRange($start, $end);

        $return = [];
        /**
         * @var array $info
         */
        foreach ($paidAmount as $info) {
            $amount       = bcmul($info['sum'], '-1');
            $nativeAmount = bcmul($info['native_sum'], '-1');
            $return[]     = [
                'key'                     => sprintf('bills-paid-in-%s', $info['currency_code']),
                'value'                   => $amount,
                'currency_id'             => $info['currency_id'],
                'currency_code'           => $info['currency_code'],
                'currency_symbol'         => $info['currency_symbol'],
                'currency_decimal_places' => $info['currency_decimal_places'],
            ];
            $return[]     = [
                'key'                     => 'bills-paid-in-native',
                'value'                   => $nativeAmount,
                'currency_id'             => $info['native_id'],
                'currency_code'           => $info['native_code'],
                'currency_symbol'         => $info['native_symbol'],
                'currency_decimal_places' => $info['native_decimal_places'],
            ];
        }

        /**
         * @var array $info
         */
        foreach ($unpaidAmount as $info) {
            $amount       = bcmul($info['sum'], '-1');
            $nativeAmount = bcmul($info['native_sum'], '-1');
            $return[]     = [
                'key'                     => sprintf('bills-unpaid-in-%s', $info['currency_code']),
                'value'                   => $amount,
                'currency_id'             => $info['currency_id'],
                'currency_code'           => $info['currency_code'],
                'currency_symbol'         => $info['currency_symbol'],
                'currency_decimal_places' => $info['currency_decimal_places'],
            ];
            $return[]     = [
                'key'                     => 'bills-unpaid-in-native',
                'value'                   => $nativeAmount,
                'currency_id'             => $info['native_id'],
                'currency_code'           => $info['native_code'],
                'currency_symbol'         => $info['native_symbol'],
                'currency_decimal_places' => $info['native_decimal_places'],
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
        $return     = [];
        $today      = today(config('app.timezone'));
        $available  = $this->abRepository->getAvailableBudgetWithCurrency($start, $end);
        $budgets    = $this->budgetRepository->getActiveBudgets();
        $spent      = $this->opsRepository->listExpenses($start, $end, null, $budgets);
        $default    = app('amount')->getDefaultCurrency();
        $currencies = [];
        $converter  = new ExchangeRateConverter();

        // native info:
        $nativeLeft   = [
            'key'                     => 'left-to-spend-in-native',
            'value'                   => '0',
            'currency_id'             => (int)$default->id,
            'currency_code'           => $default->code,
            'currency_symbol'         => $default->symbol,
            'currency_decimal_places' => (int)$default->decimal_places,
        ];
        $nativePerDay = [
            'key'                     => 'left-per-day-to-spend-in-native',
            'value'                   => '0',
            'currency_id'             => (int)$default->id,
            'currency_code'           => $default->code,
            'currency_symbol'         => $default->symbol,
            'currency_decimal_places' => (int)$default->decimal_places,
        ];

        /**
         * @var int   $currencyId
         * @var array $row
         */
        foreach ($spent as $currencyId => $row) {
            $spent       = '0';
            $spentNative = '0';
            // get the sum from the array of transactions (double loop but who cares)
            /** @var array $budget */
            foreach ($row['budgets'] as $budget) {
                /** @var array $journal */
                foreach ($budget['transaction_journals'] as $journal) {
                    $journalCurrencyId       = $journal['currency_id'];
                    $currency                = $currencies[$journalCurrencyId] ?? $this->currencyRepos->find($journalCurrencyId);
                    $currencies[$currencyId] = $currency;
                    $amount                  = bcmul($journal['amount'], '-1');
                    $amountNative            = $converter->convert($default, $currency, $start, $amount);
                    if ((int)$journal['foreign_currency_id'] === (int)$default->id) {
                        $amountNative = $journal['foreign_amount'];
                    }
                    $spent       = bcadd($spent, $amount);
                    $spentNative = bcadd($spentNative, $amountNative);
                }
            }

            // either an amount was budgeted or 0 is available.
            $currency                = $currencies[$currencyId] ?? $this->currencyRepos->find($currencyId);
            $currencies[$currencyId] = $currency;
            $amount                  = $available[$currencyId]['amount'] ?? '0';
            $amountNative            = $converter->convert($default, $currency, $start, $amount);
            $left                    = bcadd($amount, $spent);
            $leftNative              = bcadd($amountNative, $spentNative);

            // how much left per day?
            $days         = $today->diffInDays($end) + 1;
            $perDay       = '0';
            $perDayNative = '0';
            if (0 !== $days && bccomp($left, '0') > -1) {
                $perDay = bcdiv($left, (string)$days);
            }
            if (0 !== $days && bccomp($leftNative, '0') > -1) {
                $perDayNative = bcdiv($leftNative, (string)$days);
            }

            // left
            $return[] = [
                'key'                     => sprintf('left-to-spend-in-%s', $row['currency_code']),
                'value'                   => $left,
                'currency_id'             => $row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_symbol'         => $row['currency_symbol'],
                'currency_decimal_places' => $row['currency_decimal_places'],
            ];
            // left (native)
            $nativeLeft['value'] = $leftNative;

            // left per day:
            $return[] = [
                'key'                     => sprintf('left-per-day-to-spend-in-%s', $row['currency_code']),
                'value'                   => $perDay,
                'currency_id'             => $row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_symbol'         => $row['currency_symbol'],
                'currency_decimal_places' => $row['currency_decimal_places'],
            ];

            // left per day (native)
            $nativePerDay['value'] = $perDayNative;
        }
        $return[] = $nativeLeft;
        $return[] = $nativePerDay;

        return $return;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    private function getNetWorthInfo(Carbon $start, Carbon $end): array
    {
        /** @var UserGroup $userGroup */
        $userGroup = auth()->user()->userGroup;
        $date      = today(config('app.timezone'))->startOfDay();
        // start and end in the future? use $end
        if ($this->notInDateRange($date, $start, $end)) {
            /** @var Carbon $date */
            $date = session('end', today(config('app.timezone'))->endOfMonth());
        }

        /** @var NetWorthInterface $netWorthHelper */
        $netWorthHelper = app(NetWorthInterface::class);
        $netWorthHelper->setUserGroup($userGroup);
        $allAccounts = $this->accountRepository->getActiveAccountsByType(
            [AccountType::ASSET, AccountType::DEFAULT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::DEBT]
        );

        // filter list on preference of being included.
        $filtered = $allAccounts->filter(
            function (Account $account) {
                $includeNetWorth = $this->accountRepository->getMetaValue($account, 'include_net_worth');

                return null === $includeNetWorth || '1' === $includeNetWorth;
            }
        );

        $netWorthSet = $netWorthHelper->byAccounts($filtered, $date);
        $return      = [];
        // in native amount
        $return[] = [
            'key'                     => 'net-worth-in-native',
            'value'                   => $netWorthSet['native']['balance'],
            'currency_id'             => $netWorthSet['native']['currency_id'],
            'currency_code'           => $netWorthSet['native']['currency_code'],
            'currency_symbol'         => $netWorthSet['native']['currency_symbol'],
            'currency_decimal_places' => $netWorthSet['native']['currency_decimal_places'],
        ];
        foreach ($netWorthSet as $key => $data) {
            if ('native' === $key) {
                continue;
            }
            $return[] = [
                'key'                     => sprintf('net-worth-in-%s', $data['currency_code']),
                'value'                   => $data['balance'],
                'currency_id'             => $data['currency_id'],
                'currency_code'           => $data['currency_code'],
                'currency_symbol'         => $data['currency_symbol'],
                'currency_decimal_places' => $data['currency_decimal_places'],
            ];
        }

        return $return;
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
}
