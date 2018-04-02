<?php
/**
 * BoxController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Json;

use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;

/**
 * Class BoxController.
 */
class BoxController extends Controller
{
    /**
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function available(BudgetRepositoryInterface $repository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        $today = new Carbon;
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($today);
        $cache->addProperty('box-available');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        // get available amount
        $currency  = app('amount')->getDefaultCurrency();
        $available = $repository->getAvailableBudget($currency, $start, $end);

        // get spent amount:
        $budgets           = $repository->getActiveBudgets();
        $budgetInformation = $repository->collectBudgetInformation($budgets, $start, $end);
        $spent             = (string)array_sum(array_column($budgetInformation, 'spent'));
        $left              = bcadd($available, $spent);
        $days              = $today->diffInDays($end) + 1;
        $perDay            = '0';
        $text              = (string)trans('firefly.left_to_spend');
        $overspent         = false;
        if (bccomp($left, '0') === -1) {
            $text      = (string)trans('firefly.overspent');
            $overspent = true;
        }
        if (0 !== $days && bccomp($left, '0') > -1) {
            $perDay = bcdiv($left, (string)$days);
        }

        $return = [
            'perDay'    => app('amount')->formatAnything($currency, $perDay, false),
            'left'      => app('amount')->formatAnything($currency, $left, false),
            'text'      => $text,
            'overspent' => $overspent,
        ];

        $cache->store($return);

        return response()->json($return);
    }

    /**
     * @param CurrencyRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(CurrencyRepositoryInterface $repository)
    {
        // Cache result, return cache if present.
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-balance');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        // prep some arrays:
        $incomes  = [];
        $expenses = [];
        $sums     = [];

        // collect income of user:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes([TransactionType::DEPOSIT])
                  ->withOpposingAccount();
        $set = $collector->getJournals();
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId           = (int)$transaction->transaction_currency_id;
            $incomes[$currencyId] = $incomes[$currencyId] ?? '0';
            $incomes[$currencyId] = bcadd($incomes[$currencyId], $transaction->transaction_amount);
            $sums[$currencyId]    = $sums[$currencyId] ?? '0';
            $sums[$currencyId]    = bcadd($sums[$currencyId], $transaction->transaction_amount);
        }

        // collect expenses
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes([TransactionType::WITHDRAWAL])
                  ->withOpposingAccount();
        $set = $collector->getJournals();
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId            = (int)$transaction->transaction_currency_id;
            $expenses[$currencyId] = $expenses[$currencyId] ?? '0';
            $expenses[$currencyId] = bcadd($expenses[$currencyId], $transaction->transaction_amount);
            $sums[$currencyId]     = $sums[$currencyId] ?? '0';
            $sums[$currencyId]     = bcadd($sums[$currencyId], $transaction->transaction_amount);
        }

        // format amounts:
        foreach ($sums as $currencyId => $amount) {
            $currency              = $repository->findNull($currencyId);
            $sums[$currencyId]     = Amount::formatAnything($currency, $sums[$currencyId], false);
            $incomes[$currencyId]  = Amount::formatAnything($currency, $incomes[$currencyId] ?? '0', false);
            $expenses[$currencyId] = Amount::formatAnything($currency, $expenses[$currencyId] ?? '0', false);
        }

        $response = [
            'incomes'  => $incomes,
            'expenses' => $expenses,
            'sums'     => $sums,
            'size'     => count($sums),
        ];

        $cache->store($response);

        return response()->json($response);
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bills(BillRepositoryInterface $repository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-bills');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /*
         * Since both this method and the chart use the exact same data, we can suffice
         * with calling the one method in the bill repository that will get this amount.
         */
        $paidAmount   = bcmul($repository->getBillsPaidInRange($start, $end), '-1');
        $unpaidAmount = $repository->getBillsUnpaidInRange($start, $end); // will be a positive amount.
        $currency     = app('amount')->getDefaultCurrency();

        $return = [
            'paid'   => app('amount')->formatAnything($currency, $paidAmount, false),
            'unpaid' => app('amount')->formatAnything($currency, $unpaidAmount, false),
        ];
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * @param AccountRepositoryInterface  $repository
     *
     * @param CurrencyRepositoryInterface $currencyRepos
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function netWorth(AccountRepositoryInterface $repository, CurrencyRepositoryInterface $currencyRepos)
    {
        $date = new Carbon(date('Y-m-d')); // needed so its per day.
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->endOfMonth());

        // start and end in the future? use $end
        if ($start->greaterThanOrEqualTo($date) && $end->greaterThanOrEqualTo($date)) {
            $date = $end;
        }
        // start and end in the past? use $end
        if ($start->lessThanOrEqualTo($date) && $end->lessThanOrEqualTo($date)) {
            $date = $end;
        }
        // start in the past, end in the future? use $date
        $cache = new CacheProperties;
        $cache->addProperty($date);
        $cache->addProperty('box-net-worth');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $netWorth = [];
        $accounts = $repository->getActiveAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $currency = app('amount')->getDefaultCurrency();
        $balances = app('steam')->balancesByAccounts($accounts, $date);

        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountCurrency = null;
            $balance         = $balances[$account->id] ?? '0';
            $currencyId      = (int)$repository->getMetaValue($account, 'currency_id');
            if ($currencyId !== 0) {
                $accountCurrency = $currencyRepos->findNull($currencyId);
            }
            if (null === $accountCurrency) {
                $accountCurrency = $currency;
            }

            // if the account is a credit card, subtract the virtual balance from the balance,
            // to better reflect that this is not money that is actually "yours".
            $role           = (string)$repository->getMetaValue($account, 'accountRole');
            $virtualBalance = (string)$account->virtual_balance;
            if ($role === 'ccAsset' && $virtualBalance !== '' && (float)$virtualBalance > 0) {
                $balance = bcsub($balance, $virtualBalance);
            }

            if (!isset($netWorth[$accountCurrency->id])) {
                $netWorth[$accountCurrency->id]['currency'] = $accountCurrency;
                $netWorth[$accountCurrency->id]['sum']      = '0';
            }
            $netWorth[$accountCurrency->id]['sum'] = bcadd($netWorth[$accountCurrency->id]['sum'], $balance);
        }

        $return = [];
        foreach ($netWorth as $currencyId => $data) {
            $return[$currencyId] = app('amount')->formatAnything($data['currency'], $data['sum'], false);
        }
        $return = [
            'net_worths' => array_values($return),
        ];

        $cache->store($return);

        return response()->json($return);
    }
}
