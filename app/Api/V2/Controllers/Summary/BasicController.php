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
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\DateRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Http\Api\SummaryBalanceGrouped;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class BasicController
 */
class BasicController extends Controller
{
    use ValidatesUserGroupTrait;

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
                $this->abRepository      = app(AvailableBudgetRepositoryInterface::class);
                $this->accountRepository = app(AccountRepositoryInterface::class);
                $this->billRepository    = app(BillRepositoryInterface::class);
                $this->budgetRepository  = app(BudgetRepositoryInterface::class);
                $this->currencyRepos     = app(CurrencyRepositoryInterface::class);
                $this->opsRepository     = app(OperationsRepositoryInterface::class);

                $userGroup               = $this->validateUserGroup($request);
                $this->abRepository->setUserGroup($userGroup);
                $this->accountRepository->setUserGroup($userGroup);
                $this->billRepository->setUserGroup($userGroup);
                $this->budgetRepository->setUserGroup($userGroup);
                $this->opsRepository->setUserGroup($userGroup);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v2)#/summary/getBasicSummary
     *
     * @throws \Exception
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function basic(DateRequest $request): JsonResponse
    {
        // parameters for boxes:
        $start        = $this->parameters->get('start');
        $end          = $this->parameters->get('end');

        // balance information:
        $balanceData  = $this->getBalanceInformation($start, $end);
        $billData     = $this->getBillInformation($start, $end);
        $spentData    = $this->getLeftToSpendInfo($start, $end);
        $netWorthData = $this->getNetWorthInfo($start, $end);
        $total        = array_merge($balanceData, $billData, $spentData, $netWorthData);

        return response()->json($total);
    }

    /**
     * @throws FireflyException
     */
    private function getBalanceInformation(Carbon $start, Carbon $end): array
    {
        $object    = new SummaryBalanceGrouped();
        $default   = app('amount')->getDefaultCurrency();

        $object->setDefault($default);

        /** @var User $user */
        $user      = auth()->user();

        // collect income of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setRange($start, $end)
            ->setUserGroup($user->userGroup)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes([TransactionTypeEnum::DEPOSIT->value])
            ->setRange($start, $end)
        ;

        $set       = $collector->getExtractedJournals();
        $object->groupTransactions('income', $set);

        // collect expenses of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setRange($start, $end)
            ->setUserGroup($user->userGroup)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes([TransactionTypeEnum::WITHDRAWAL->value])
            ->setRange($start, $end)
        ;
        $set       = $collector->getExtractedJournals();
        $object->groupTransactions('expense', $set);

        return $object->groupData();
    }

    private function getBillInformation(Carbon $start, Carbon $end): array
    {
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
            $amount       = bcmul($info['sum'], '-1');
            $nativeAmount = bcmul($info['native_sum'], '-1');
            $return[]     = [
                'key'                     => sprintf('bills-paid-in-%s', $info['currency_code']),
                'value'                   => $amount,
                'currency_id'             => (string) $info['currency_id'],
                'currency_code'           => $info['currency_code'],
                'currency_symbol'         => $info['currency_symbol'],
                'currency_decimal_places' => $info['currency_decimal_places'],
            ];
            $return[]     = [
                'key'                     => 'bills-paid-in-native',
                'value'                   => $nativeAmount,
                'currency_id'             => (string) $info['native_currency_id'],
                'currency_code'           => $info['native_currency_code'],
                'currency_symbol'         => $info['native_currency_symbol'],
                'currency_decimal_places' => $info['native_currency_decimal_places'],
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
                'currency_id'             => (string) $info['currency_id'],
                'currency_code'           => $info['currency_code'],
                'currency_symbol'         => $info['currency_symbol'],
                'currency_decimal_places' => $info['currency_decimal_places'],
            ];
            $return[]     = [
                'key'                     => 'bills-unpaid-in-native',
                'value'                   => $nativeAmount,
                'currency_id'             => (string) $info['native_currency_id'],
                'currency_code'           => $info['native_currency_code'],
                'currency_symbol'         => $info['native_currency_symbol'],
                'currency_decimal_places' => $info['native_currency_decimal_places'],
            ];
        }

        return $return;
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function getLeftToSpendInfo(Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Created new ExchangeRateConverter in %s', __METHOD__));
        app('log')->debug('Now in getLeftToSpendInfo');
        $return       = [];
        $today        = today(config('app.timezone'));
        $available    = $this->abRepository->getAvailableBudgetWithCurrency($start, $end);
        $budgets      = $this->budgetRepository->getActiveBudgets();
        $spent        = $this->opsRepository->listExpenses($start, $end, null, $budgets);
        $default      = app('amount')->getDefaultCurrency();
        $currencies   = [];
        $converter    = new ExchangeRateConverter();

        // native info:
        $nativeLeft   = [
            'key'                     => 'left-to-spend-in-native',
            'value'                   => '0',
            'currency_id'             => (string) $default->id,
            'currency_code'           => $default->code,
            'currency_symbol'         => $default->symbol,
            'currency_decimal_places' => $default->decimal_places,
        ];
        $nativePerDay = [
            'key'                     => 'left-per-day-to-spend-in-native',
            'value'                   => '0',
            'currency_id'             => (string) $default->id,
            'currency_code'           => $default->code,
            'currency_symbol'         => $default->symbol,
            'currency_decimal_places' => $default->decimal_places,
        ];

        /**
         * @var int   $currencyId
         * @var array $row
         */
        foreach ($spent as $currencyId => $row) {
            app('log')->debug(sprintf('Processing spent array in currency #%d', $currencyId));
            $spent                   = '0';
            $spentNative             = '0';

            // get the sum from the array of transactions (double loop but who cares)
            /** @var array $budget */
            foreach ($row['budgets'] as $budget) {
                app('log')->debug(sprintf('Processing expenses in budget "%s".', $budget['name']));

                /** @var array $journal */
                foreach ($budget['transaction_journals'] as $journal) {
                    $journalCurrencyId       = $journal['currency_id'];
                    $currency                = $currencies[$journalCurrencyId] ?? $this->currencyRepos->find($journalCurrencyId);
                    $currencies[$currencyId] = $currency;
                    $amount                  = app('steam')->negative($journal['amount']);
                    $amountNative            = $converter->convert($default, $currency, $start, $amount);
                    if ((int) $journal['foreign_currency_id'] === $default->id) {
                        $amountNative = $journal['foreign_amount'];
                    }
                    $spent                   = bcadd($spent, $amount);
                    $spentNative             = bcadd($spentNative, $amountNative);
                }
                app('log')->debug(sprintf('Total spent in budget "%s" is %s', $budget['name'], $spent));
            }

            // either an amount was budgeted or 0 is available.
            $currency                = $currencies[$currencyId] ?? $this->currencyRepos->find($currencyId);
            $currencies[$currencyId] = $currency;
            $amount                  = $available[$currencyId]['amount'] ?? '0';
            $amountNative            = $available[$currencyId]['native_amount'] ?? '0';
            $left                    = bcadd($amount, $spent);
            $leftNative              = bcadd($amountNative, $spentNative);
            app('log')->debug(sprintf('Available amount is %s', $amount));
            app('log')->debug(sprintf('Amount left is %s', $left));

            // how much left per day?
            $days                    = (int) $today->diffInDays($end, true) + 1;
            $perDay                  = '0';
            $perDayNative            = '0';
            if (0 !== $days && bccomp($left, '0') > -1) {
                $perDay = bcdiv($left, (string) $days);
            }
            if (0 !== $days && bccomp($leftNative, '0') > -1) {
                $perDayNative = bcdiv($leftNative, (string) $days);
            }

            // left
            $return[]                = [
                'key'                     => sprintf('left-to-spend-in-%s', $row['currency_code']),
                'value'                   => $left,
                'currency_id'             => (string) $row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_symbol'         => $row['currency_symbol'],
                'currency_decimal_places' => (int) $row['currency_decimal_places'],
            ];
            // left (native)
            $nativeLeft['value']     = $leftNative;

            // left per day:
            $return[]                = [
                'key'                     => sprintf('left-per-day-to-spend-in-%s', $row['currency_code']),
                'value'                   => $perDay,
                'currency_id'             => (string) $row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_symbol'         => $row['currency_symbol'],
                'currency_decimal_places' => (int) $row['currency_decimal_places'],
            ];

            // left per day (native)
            $nativePerDay['value']   = $perDayNative;
        }
        $return[]     = $nativeLeft;
        $return[]     = $nativePerDay;
        $converter->summarize();

        return $return;
    }

    private function getNetWorthInfo(Carbon $start, Carbon $end): array
    {
        /** @var UserGroup $userGroup */
        $userGroup      = auth()->user()->userGroup;
        $date           = today(config('app.timezone'))->startOfDay();
        // start and end in the future? use $end
        if ($this->notInDateRange($date, $start, $end)) {
            /** @var Carbon $date */
            $date = session('end', today(config('app.timezone'))->endOfMonth());
        }

        /** @var NetWorthInterface $netWorthHelper */
        $netWorthHelper = app(NetWorthInterface::class);
        $netWorthHelper->setUserGroup($userGroup);
        $allAccounts    = $this->accountRepository->getActiveAccountsByType(
            [AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::DEBT->value]
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
        // in native amount
        $return[]       = [
            'key'                     => 'net-worth-in-native',
            'value'                   => $netWorthSet['native']['balance'],
            'currency_id'             => (string) $netWorthSet['native']['currency_id'],
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
                'currency_id'             => (string) $data['currency_id'],
                'currency_code'           => $data['currency_code'],
                'currency_symbol'         => $data['currency_symbol'],
                'currency_decimal_places' => $data['currency_decimal_places'],
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
