<?php
/**
 * BoxController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Response;

/**
 * Class BoxController
 *
 * @package FireflyIII\Http\Controllers\Json
 */
class BoxController extends Controller
{
    /**
     *
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
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        // get available amount
        $currency  = app('amount')->getDefaultCurrency();
        $available = $repository->getAvailableBudget($currency, $start, $end);


        // get spent amount:
        $budgets           = $repository->getActiveBudgets();
        $budgetInformation = $repository->collectBudgetInformation($budgets, $start, $end);
        $spent             = strval(array_sum(array_column($budgetInformation, 'spent')));
        $left              = bcadd($available, $spent);
        // left less than zero? then it's zero:
        if (bccomp($left, '0') === -1) {
            $left = '0';
        }
        $days   = $today->diffInDays($end);
        $perDay = '0';
        if ($days !== 0) {
            $perDay = bcdiv($left, strval($days));
        }

        $return = [
            'perDay' => app('amount')->formatAnything($currency, $perDay, false),
            'left'   => app('amount')->formatAnything($currency, $left, false),
        ];

        $cache->store($return);

        return Response::json($return);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance()
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-balance');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        // try a collector for income:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes([TransactionType::DEPOSIT])
                  ->withOpposingAccount();
        $income   = strval($collector->getJournals()->sum('transaction_amount'));
        $currency = app('amount')->getDefaultCurrency();

        // expense:
        // try a collector for expenses:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes([TransactionType::WITHDRAWAL])
                  ->withOpposingAccount();
        $expense = strval($collector->getJournals()->sum('transaction_amount'));

        $response = [
            'income'   => app('amount')->formatAnything($currency, $income, false),
            'expense'  => app('amount')->formatAnything($currency, $expense, false),
            'combined' => app('amount')->formatAnything($currency, bcadd($income, $expense), false),
        ];

        $cache->store($response);

        return Response::json($response);
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
            return Response::json($cache->get()); // @codeCoverageIgnore
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

        return Response::json($return);
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function netWorth(AccountRepositoryInterface $repository)
    {
        $today = new Carbon(date('Y-m-d')); // needed so its per day.
        $cache = new CacheProperties;
        $cache->addProperty($today);
        $cache->addProperty('box-net-worth');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }
        $accounts = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $currency = app('amount')->getDefaultCurrency();
        $balances = app('steam')->balancesByAccounts($accounts, $today);
        $sum      = '0';
        foreach ($balances as $entry) {
            $sum = bcadd($sum, $entry);
        }

        $return = [
            'net_worth' => app('amount')->formatAnything($currency, $sum, false),
        ];

        $cache->store($return);

        return Response::json($return);
    }

}