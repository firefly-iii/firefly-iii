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

use Exception;
use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\DateRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Report\Summarizer\TransactionSummarizer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
     * @throws Exception
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
        $billData     = $this->getSubscriptionInformation($start, $end);
        $spentData    = $this->getLeftToSpendInfo($start, $end);
        $netWorthData = $this->getNetWorthInfo($end);
        //                        $balanceData  = [];
        //                        $billData     = [];
        //                $spentData    = [];
        //                        $netWorthData = [];
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
        Log::debug('getBalanceInformation');
        // some config settings
        $convertToPrimary = Amount::convertToPrimary();
        $primary          = Amount::getPrimaryCurrency();
        // prep some arrays:
        $sums             = [];
        $return           = [];
        $currencies       = [
            $primary->id => $primary,
        ];

        // collect income of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector        = app(GroupCollectorInterface::class);
        $summarizer       = new TransactionSummarizer();
        $set              = $collector->setRange($start, $end)->setTypes([TransactionTypeEnum::DEPOSIT->value])->getExtractedJournals();
        $incomes          = $summarizer->groupByCurrencyId($set, 'positive', false);


        // collect expenses of user.
        // collect expenses of user using the new group collector.
        /** @var GroupCollectorInterface $collector */
        $collector        = app(GroupCollectorInterface::class);
        $set              = $collector->setRange($start, $end)->setPage($this->parameters->get('page'))->setTypes([TransactionTypeEnum::WITHDRAWAL->value])->getExtractedJournals();
        $expenses         = $summarizer->groupByCurrencyId($set, 'negative', false);

        // if convert to primary, do so right now.
        if ($convertToPrimary) {
            $newExpenses = [
                $primary->id => [
                    'currency_id'             => $primary->id,
                    'currency_code'           => $primary->code,
                    'currency_symbol'         => $primary->symbol,
                    'currency_decimal_places' => $primary->decimal_places,
                    'sum'                     => '0',
                ],
            ];
            $newIncomes  = [
                $primary->id => [
                    'currency_id'             => $primary->id,
                    'currency_code'           => $primary->code,
                    'currency_symbol'         => $primary->symbol,
                    'currency_decimal_places' => $primary->decimal_places,
                    'sum'                     => '0',
                ],
            ];
            $sums        = [
                $primary->id => [
                    'currency_id'             => $primary->id,
                    'currency_code'           => $primary->code,
                    'currency_symbol'         => $primary->symbol,
                    'currency_decimal_places' => $primary->decimal_places,
                    'sum'                     => '0',
                ],
            ];

            $converter   = new ExchangeRateConverter();
            // loop over income and expenses
            foreach ([$expenses, $incomes] as $index => $array) {

                // loop over either one.
                foreach ($array as $entry) {

                    // if it is the primary currency already.
                    if ($entry['currency_id'] === $primary->id) {
                        $sums[$primary->id]['sum'] = bcadd((string) $entry['sum'], $sums[$primary->id]['sum']);

                        // don't forget to add it to newExpenses and newIncome
                        if (0 === $index) {
                            $newExpenses[$primary->id]['sum'] = bcadd($newExpenses[$primary->id]['sum'], (string) $entry['sum']);
                        }
                        if (1 === $index) {
                            $newIncomes[$primary->id]['sum'] = bcadd($newIncomes[$primary->id]['sum'], (string) $entry['sum']);
                        }

                        continue;
                    }

                    $currencies[$entry['currency_id']] ??= $this->currencyRepos->find($entry['currency_id']);
                    $convertedSum              = $converter->convert($currencies[$entry['currency_id']], $primary, $start, $entry['sum']);
                    $sums[$primary->id]['sum'] = bcadd($sums[$primary->id]['sum'], $convertedSum);
                    if (0 === $index) {
                        $newExpenses[$primary->id]['sum'] = bcadd($newExpenses[$primary->id]['sum'], $convertedSum);
                    }
                    if (1 === $index) {
                        $newIncomes[$primary->id]['sum'] = bcadd($newIncomes[$primary->id]['sum'], $convertedSum);
                    }
                }
            }
            $incomes     = $newIncomes;
            $expenses    = $newExpenses;
        }
        if (!$convertToPrimary) {
            foreach ([$expenses, $incomes] as $array) {
                foreach ($array as $entry) {
                    $currencyId               = $entry['currency_id'];
                    $sums[$currencyId] ??= [
                        'currency_id'             => $entry['currency_id'],
                        'currency_code'           => $entry['currency_code'],
                        'currency_symbol'         => $entry['currency_symbol'],
                        'currency_decimal_places' => $entry['currency_decimal_places'],
                        'sum'                     => '0',
                    ];
                    $sums[$currencyId]['sum'] = bcadd($sums[$currencyId]['sum'], (string) $entry['sum']);
                }
            }
        }
        // format amounts:
        $keys             = array_keys($sums);
        foreach ($keys as $currencyId) {
            $currency = $currencies[$currencyId] ?? $this->currencyRepos->find($currencyId);
            if (null === $currency) {
                continue;
            }
            // create objects for big array.
            $return[] = [
                'key'                     => sprintf('balance-in-%s', $currency->code),
                'title'                   => trans('firefly.box_balance_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => $sums[$currencyId]['sum'] ?? '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => Amount::formatAnything($currency, $sums[$currencyId]['sum'] ?? '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => Amount::formatAnything($currency, $expenses[$currencyId]['sum'] ?? '0', false)
                                             .' + '.Amount::formatAnything($currency, $incomes[$currencyId]['sum'] ?? '0', false),
            ];
            $return[] = [
                'key'                     => sprintf('spent-in-%s', $currency->code),
                'title'                   => trans('firefly.box_spent_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => $expenses[$currencyId]['sum'] ?? '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => Amount::formatAnything($currency, $expenses[$currencyId]['sum'] ?? '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => '',
            ];
            $return[] = [
                'key'                     => sprintf('earned-in-%s', $currency->code),
                'title'                   => trans('firefly.box_earned_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => $incomes[$currencyId]['sum'] ?? '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => Amount::formatAnything($currency, $incomes[$currencyId]['sum'] ?? '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => '',
            ];
        }
        if (0 === count($return)) {
            $currency = $this->primaryCurrency;
            // create objects for big array.
            $return[] = [
                'key'                     => sprintf('balance-in-%s', $currency->code),
                'title'                   => trans('firefly.box_balance_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => Amount::formatAnything($currency, '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => Amount::formatAnything($currency, '0', false)
                                             .' + '.Amount::formatAnything($currency, '0', false),
            ];
            $return[] = [
                'key'                     => sprintf('spent-in-%s', $currency->code),
                'title'                   => trans('firefly.box_spent_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => Amount::formatAnything($currency, '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => '',
            ];
            $return[] = [
                'key'                     => sprintf('earned-in-%s', $currency->code),
                'title'                   => trans('firefly.box_earned_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => Amount::formatAnything($currency, '0', false),
                'local_icon'              => 'balance-scale',
                'sub_title'               => '',
            ];
        }

        return $return;
    }

    private function getSubscriptionInformation(Carbon $start, Carbon $end): array
    {
        Log::debug(sprintf('Now in getBillInformation("%s", "%s")', $start->format('Y-m-d'), $end->format('Y-m-d-')));
        /*
         * Since both this method and the chart use the exact same data, we can suffice
         * with calling the one method in the bill repository that will get this amount.
         */
        $paidAmount   = $this->billRepository->sumPaidInRange($start, $end);
        $unpaidAmount = $this->billRepository->sumUnpaidInRange($start, $end);
        $currencies   = [
            $this->primaryCurrency->id => $this->primaryCurrency,
        ];

        if ($this->convertToPrimary) {
            $converter       = new ExchangeRateConverter();
            $newPaidAmount   = [[
                'id'             => $this->primaryCurrency->id,
                'name'           => $this->primaryCurrency->name,
                'symbol'         => $this->primaryCurrency->symbol,
                'code'           => $this->primaryCurrency->code,
                'decimal_places' => $this->primaryCurrency->decimal_places,
                'sum'            => '0',
            ]];

            $newUnpaidAmount = [[
                'id'             => $this->primaryCurrency->id,
                'name'           => $this->primaryCurrency->name,
                'symbol'         => $this->primaryCurrency->symbol,
                'code'           => $this->primaryCurrency->code,
                'decimal_places' => $this->primaryCurrency->decimal_places,
                'sum'            => '0',
            ]];
            foreach ([$paidAmount, $unpaidAmount] as $index => $array) {
                foreach ($array as $item) {
                    $currencyId                = (int) $item['id'];
                    if (0 === $index) {
                        // paid amount
                        if ($currencyId === $this->primaryCurrency->id) {
                            $newPaidAmount[0]['sum'] = bcadd($newPaidAmount[0]['sum'], (string) $item['sum']);

                            continue;
                        }
                        $currencies[$currencyId] ??= $this->currencyRepos->find($currencyId);
                        $convertedAmount         = $converter->convert($currencies[$currencyId], $this->primaryCurrency, $start, $item['sum']);
                        $newPaidAmount[0]['sum'] = bcadd($newPaidAmount[0]['sum'], $convertedAmount);

                        continue;
                    }
                    // unpaid amount
                    if ($currencyId === $this->primaryCurrency->id) {
                        $newUnpaidAmount[0]['sum'] = bcadd($newUnpaidAmount[0]['sum'], (string) $item['sum']);

                        continue;
                    }
                    $currencies[$currencyId] ??= $this->currencyRepos->find($currencyId);
                    $convertedAmount           = $converter->convert($currencies[$currencyId], $this->primaryCurrency, $start, $item['sum']);
                    $newUnpaidAmount[0]['sum'] = bcadd($newUnpaidAmount[0]['sum'], $convertedAmount);
                }
            }
            $paidAmount      = $newPaidAmount;
            $unpaidAmount    = $newUnpaidAmount;
        }

        //        var_dump($paidAmount);
        //        var_dump($unpaidAmount);
        //        exit;

        $return       = [];

        /**
         * @var array $info
         */
        foreach ($paidAmount as $info) {
            $amount   = bcmul((string) $info['sum'], '-1');
            $return[] = [
                'key'                     => sprintf('bills-paid-in-%s', $info['code']),
                'title'                   => trans('firefly.box_bill_paid_in_currency', ['currency' => $info['symbol']]),
                'monetary_value'          => $amount,
                'currency_id'             => (string) $info['id'],
                'currency_code'           => $info['code'],
                'currency_symbol'         => $info['symbol'],
                'currency_decimal_places' => $info['decimal_places'],
                'value_parsed'            => Amount::formatFlat($info['symbol'], $info['decimal_places'], $amount, false),
                'local_icon'              => 'check',
                'sub_title'               => '',
            ];
        }

        /**
         * @var array $info
         */
        foreach ($unpaidAmount as $info) {
            $amount   = bcmul((string) $info['sum'], '-1');
            $return[] = [
                'key'                     => sprintf('bills-unpaid-in-%s', $info['code']),
                'title'                   => trans('firefly.box_bill_unpaid_in_currency', ['currency' => $info['symbol']]),
                'monetary_value'          => $amount,
                'currency_id'             => (string) $info['id'],
                'currency_code'           => $info['code'],
                'currency_symbol'         => $info['symbol'],
                'currency_decimal_places' => $info['decimal_places'],
                'value_parsed'            => Amount::formatFlat($info['symbol'], $info['decimal_places'], $amount, false),
                'local_icon'              => 'calendar-o',
                'sub_title'               => '',
            ];
        }
        Log::debug(sprintf('Done with getBillInformation("%s", "%s")', $start->format('Y-m-d'), $end->format('Y-m-d-')));

        if (0 === count($return)) {
            $currency = $this->primaryCurrency;
            unset($info, $amount);

            $return[] = [
                'key'                     => sprintf('bills-paid-in-%s', $currency->code),
                'title'                   => trans('firefly.box_bill_paid_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => Amount::formatFlat($currency->symbol, $currency->decimal_places, '0', false),
                'local_icon'              => 'check',
                'sub_title'               => '',
            ];
            $return[] = [
                'key'                     => sprintf('bills-unpaid-in-%s', $currency->code),
                'title'                   => trans('firefly.box_bill_unpaid_in_currency', ['currency' => $currency->symbol]),
                'monetary_value'          => '0',
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'value_parsed'            => Amount::formatFlat($currency->symbol, $currency->decimal_places, '0', false),
                'local_icon'              => 'calendar-o',
                'sub_title'               => '',
            ];
        }


        return $return;
    }

    /**
     * @throws Exception
     */
    private function getLeftToSpendInfo(Carbon $start, Carbon $end): array
    {

        Log::debug(sprintf('Now in getLeftToSpendInfo("%s", "%s")', $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));
        $return     = [];
        $today      = today(config('app.timezone'));
        $available  = $this->abRepository->getAvailableBudgetWithCurrency($start, $end);
        $budgets    = $this->budgetRepository->getActiveBudgets();
        $spent      = $this->opsRepository->sumExpenses($start, $end, null, $budgets);
        $days       = (int) $today->diffInDays($end, true) + 1;
        $currencies = [];

        // first, create an entry for each entry in the "available" array.
        /** @var array $availableBudget */
        foreach ($available as $currencyId => $availableBudget) {
            $currencies[$currencyId] ??= $this->currencyRepos->find($currencyId);
            $return[$currencyId] = [
                'key'                     => sprintf('left-to-spend-in-%s', $currencies[$currencyId]->code),
                'title'                   => trans('firefly.box_left_to_spend_in_currency', ['currency' => $currencies[$currencyId]->symbol]),
                'no_available_budgets'    => false,
                'monetary_value'          => $availableBudget,
                'currency_id'             => (string) $currencies[$currencyId]->id,
                'currency_code'           => $currencies[$currencyId]->code,
                'currency_symbol'         => $currencies[$currencyId]->symbol,
                'currency_decimal_places' => $currencies[$currencyId]->decimal_places,
                'value_parsed'            => Amount::formatFlat($currencies[$currencyId]->symbol, $currencies[$currencyId]->decimal_places, $availableBudget, false),
                'local_icon'              => 'money',
                'sub_title'               => Amount::formatFlat($currencies[$currencyId]->symbol, $currencies[$currencyId]->decimal_places, $availableBudget, false),
            ];
        }
        foreach ($spent as $row) {
            // either an amount was budgeted or 0 is available.
            $currencyId          = (int) $row['currency_id'];
            $amount              = (string) ($available[$currencyId] ?? '0');
            if (0 === bccomp($amount, '0')) {
                // #9858 skip over currencies with no available budget.
                continue;
            }
            $spentInCurrency     = $row['sum'];
            $leftToSpend         = bcadd($amount, (string) $spentInCurrency);
            $perDay              = '0';
            if (0 !== $days && bccomp($leftToSpend, '0') > -1) {
                $perDay = bcdiv($leftToSpend, (string) $days);
            }

            Log::debug(sprintf('Spent %s %s', $row['currency_code'], $row['sum']));

            $return[$currencyId] = [
                'key'                     => sprintf('left-to-spend-in-%s', $row['currency_code']),
                'title'                   => trans('firefly.box_left_to_spend_in_currency', ['currency' => $row['currency_symbol']]),
                'no_available_budgets'    => false,
                'monetary_value'          => $leftToSpend,
                'currency_id'             => (string) $row['currency_id'],
                'currency_code'           => $row['currency_code'],
                'currency_symbol'         => $row['currency_symbol'],
                'currency_decimal_places' => $row['currency_decimal_places'],
                'value_parsed'            => Amount::formatFlat($row['currency_symbol'], $row['currency_decimal_places'], $leftToSpend, false),
                'local_icon'              => 'money',
                'sub_title'               => Amount::formatFlat($row['currency_symbol'], $row['currency_decimal_places'], $perDay, false),
            ];
        }
        unset($leftToSpend);
        if (0 === count($return)) {
            $days  = (int) $start->diffInDays($end, true) + 1;
            // a small trick to get every expense in this period, regardless of budget.
            $spent = $this->opsRepository->sumExpenses($start, $end, null, new Collection());
            foreach ($spent as $row) {
                // either an amount was budgeted or 0 is available.
                $currencyId          = (int) $row['currency_id'];
                $spentInCurrency     = $row['sum'];
                $perDay              = '0';
                if (0 !== $days && -1 === bccomp((string) $spentInCurrency, '0')) {
                    $perDay = bcdiv((string) $spentInCurrency, (string) $days);
                }

                Log::debug(sprintf('Spent %s %s', $row['currency_code'], $row['sum']));

                $return[$currencyId] = [
                    'key'                     => sprintf('left-to-spend-in-%s', $row['currency_code']),
                    'title'                   => trans('firefly.spent'),
                    'no_available_budgets'    => true,
                    'monetary_value'          => $spentInCurrency,
                    'currency_id'             => (string) $row['currency_id'],
                    'currency_code'           => $row['currency_code'],
                    'currency_symbol'         => $row['currency_symbol'],
                    'currency_decimal_places' => $row['currency_decimal_places'],
                    'value_parsed'            => Amount::formatFlat($row['currency_symbol'], $row['currency_decimal_places'], $spentInCurrency, false),
                    'local_icon'              => 'money',
                    'sub_title'               => Amount::formatFlat($row['currency_symbol'], $row['currency_decimal_places'], $perDay, false),
                ];
            }

            //            $amount = '0';
            //            // $days
            //            // fill in by money spent, just count it.
            //            $currency              = $this->primaryCurrency;
            //            $return[$currency->id] = [
            //                'key'                     => sprintf('left-to-spend-in-%s', $currency->code),
            //                'title'                   => trans('firefly.box_left_to_spend_in_currency', ['currency' => $currency->symbol]),
            //                'monetary_value'          => '0',
            //                'no_available_budgets'    => true,
            //                'currency_id'             => (string) $currency->id,
            //                'currency_code'           => $currency->code,
            //                'currency_symbol'         => $currency->symbol,
            //                'currency_decimal_places' => $currency->decimal_places,
            //                'value_parsed'            => Amount::formatFlat($currency->symbol, $currency->decimal_places, '0', false),
            //                'local_icon'              => 'money',
            //                'sub_title'               => Amount::formatFlat(
            //                    $currency->symbol,
            //                    $currency->decimal_places,
            //                    '0',
            //                    false
            //                ),
            //            ];
        }

        return array_values($return);
    }

    private function getNetWorthInfo(Carbon $end): array
    {
        $end->endOfDay();

        /** @var User $user */
        $user           = auth()->user();
        Log::debug(sprintf('getNetWorthInfo up until "%s".', $end->format('Y-m-d H:i:s')));

        /** @var NetWorthInterface $netWorthHelper */
        $netWorthHelper = app(NetWorthInterface::class);
        $netWorthHelper->setUser($user);
        $allAccounts    = $this->accountRepository->getActiveAccountsByType([AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::DEBT->value]);

        // filter list on preference of being included.
        $filtered       = $allAccounts->filter(
            function (Account $account) {
                $includeNetWorth = $this->accountRepository->getMetaValue($account, 'include_net_worth');

                return null === $includeNetWorth || '1' === $includeNetWorth;
            }
        );

        $netWorthSet    = $netWorthHelper->byAccounts($filtered, $end);
        $return         = [];
        foreach ($netWorthSet as $key => $data) {
            if ('pc' === $key) {
                continue;
            }
            $amount   = $data['balance'];
            if (0 === bccomp((string) $amount, '0')) {
                continue;
            }
            // return stuff
            $return[] = [
                'key'                     => sprintf('net-worth-in-%s', $data['currency_code']),
                'title'                   => trans('firefly.box_net_worth_in_currency', ['currency' => $data['currency_symbol']]),
                'monetary_value'          => $amount,
                'currency_id'             => (string) $data['currency_id'],
                'currency_code'           => $data['currency_code'],
                'currency_symbol'         => $data['currency_symbol'],
                'currency_decimal_places' => $data['currency_decimal_places'],
                'value_parsed'            => Amount::formatFlat($data['currency_symbol'], $data['currency_decimal_places'], $data['balance'], false),
                'local_icon'              => 'line-chart',
                'sub_title'               => '',
            ];
        }
        if (0 === count($return)) {
            $return[] = [
                'key'                     => sprintf('net-worth-in-%s', $this->primaryCurrency->code),
                'title'                   => trans('firefly.box_net_worth_in_currency', ['currency' => $this->primaryCurrency->symbol]),
                'monetary_value'          => '0',
                'currency_id'             => (string) $this->primaryCurrency->id,
                'currency_code'           => $this->primaryCurrency->code,
                'currency_symbol'         => $this->primaryCurrency->symbol,
                'currency_decimal_places' => $this->primaryCurrency->decimal_places,
                'value_parsed'            => Amount::formatFlat($this->primaryCurrency->symbol, $this->primaryCurrency->decimal_places, '0', false),
                'local_icon'              => 'line-chart',
                'sub_title'               => '',
            ];
        }


        Log::debug('End of getNetWorthInfo');

        return $return;
    }

    /**
     * Check if date is outside session range.
     */
    protected function notInDateRange(Carbon $date, Carbon $start, Carbon $end): bool // Validate a preference
    {
        if ($start->greaterThanOrEqualTo($date) && $end->greaterThanOrEqualTo($date)) {
            return true;
        }
        // start and end in the past? use $end
        if ($start->lessThanOrEqualTo($date) && $end->lessThanOrEqualTo($date)) {
            return true;
        }

        return false;
    }
}
