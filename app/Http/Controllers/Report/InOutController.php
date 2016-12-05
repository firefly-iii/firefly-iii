<?php
/**
 * InOutController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Report;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;

/**
 * Class InOutController
 *
 * @package FireflyIII\Http\Controllers\Report
 */
class InOutController extends Controller
{

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseReport(Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expense-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        // get all expenses for the given accounts in the given period!
        // also transfers!
        // get all transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->withOpposingAccount()
                  ->enableInternalFilter();
        $transactions = $collector->getJournals();
        $transactions = $transactions->filter(
            function (Transaction $transaction) {
                // return negative amounts only.
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return false;
            }
        );
        $expenses     = $this->groupByOpposing($transactions);

        // sort the result
        // Obtain a list of columns
        $sum = [];
        foreach ($expenses as $accountId => $row) {
            $sum[$accountId] = floatval($row['sum']);
        }

        // Sort the data with volume descending, edition ascending
        // Add $data as the last parameter, to sort by the common key
        array_multisort($sum, SORT_ASC, $expenses);

        $result = view('reports.partials.expenses', compact('expenses'))->render();
        $cache->store($result);

        return $result;

    }

    /**
     * @param ReportHelperInterface $helper
     * @param Carbon                $start
     * @param Carbon                $end
     * @param Collection            $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function incExpReport(ReportHelperInterface $helper, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('inc-exp-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get();
        }

        $incomes  = $helper->getIncomeReport($start, $end, $accounts);
        $expenses = $helper->getExpenseReport($start, $end, $accounts);

        $result = view('reports.partials.income-vs-expenses', compact('expenses', 'incomes'))->render();
        $cache->store($result);

        return $result;

    }

    /**
     * @param ReportHelperInterface $helper
     * @param Carbon                $start
     * @param Carbon                $end
     * @param Collection            $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function incomeReport(ReportHelperInterface $helper, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('income-report');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            //return $cache->get();
        }

        // get all expenses for the given accounts in the given period!
        // also transfers!
        // get all transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->withOpposingAccount()
                  ->enableInternalFilter();
        $transactions = $collector->getJournals();
        $transactions = $transactions->filter(
            function (Transaction $transaction) {
                // return positive amounts only.
                if (bccomp($transaction->transaction_amount, '0') === 1) {
                    return $transaction;
                }

                return false;
            }
        );
        $income       = $this->groupByOpposing($transactions);

        $result = view('reports.partials.income', compact('income'))->render();
        $cache->store($result);

        return $result;

    }

    /**
     * @param Collection $transactions
     *
     * @return array
     */
    private function groupByOpposing(Collection $transactions): array
    {
        $expenses = [];
        // join the result together:
        foreach ($transactions as $transaction) {
            $opposingId = $transaction->opposing_account_id;
            $name       = $transaction->opposing_account_name;
            if (!isset($expenses[$opposingId])) {
                $expenses[$opposingId] = [
                    'id'    => $opposingId,
                    'name'  => $name,
                    'sum'   => '0',
                    'count' => 0,
                ];
            }
            $expenses[$opposingId]['sum'] = bcadd($expenses[$opposingId]['sum'], $transaction->transaction_amount);
            $expenses[$opposingId]['count']++;
        }


        return $expenses;
    }

}