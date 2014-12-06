<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 06/12/14
 * Time: 08:40
 */

namespace FireflyIII\Database;


use Carbon\Carbon;
use FireflyIII\Database\Ifaces\ReportInterface;

class Report implements ReportInterface
{
    use SwitchUser;


    /**
     * @param \Account $account
     * @param Carbon   $month
     *
     * @return float
     */
    public function getExpenseByMonth(\Account $account, Carbon $month)
    {
        if (isset($account->sharedExpense) && $account->sharedExpense === true) {
            $shared = true;
        } else {
            if (isset($account->sharedExpense) && $account->sharedExpense === false) {
                $shared = false;
            } else {
                $shared = ($account->getMeta('accountRole') == 'sharedExpense');
            }
        }

        $start = clone $month;
        $end   = clone $month;
        $start->startOfMonth();
        $end->endOfMonth();
        $sum = 0;

        // get all journals.
        $journals = \TransactionJournal::whereIn(
            'id', function ($query) use ($account, $start, $end) {
                $query->select('transaction_journal_id')
                      ->from('transactions')
                      ->where('account_id', $account->id);
            }
        )->before($end)->after($start)->get();


        if ($shared) {
            $expenses = $journals->filter(
                function (\TransactionJournal $journal) use ($account) {
                    // any withdrawal is an expense:
                    if ($journal->transactionType->type == 'Withdrawal') {
                        return $journal;
                    }
                    // any transfer away from this account is an expense.
                    if ($journal->transactionType->type == 'Transfer') {
                        /** @var \Transaction $t */
                        foreach ($journal->transactions as $t) {
                            if ($t->account_id == $account->id && floatval($t->amount) < 0) {
                                return $journal;
                            }
                        }
                    }
                }
            );
        } else {
            $expenses = $journals->filter(
                function (\TransactionJournal $journal) use ($account) {
                    // only withdrawals are expenses:
                    if ($journal->transactionType->type == 'Withdrawal') {
                        return $journal;
                    }
                    // transfers TO a shared account are also expenses.
                    if ($journal->transactionType->type == 'Transfer') {
                        /** @var \Transaction $t */
                        foreach ($journal->transactions() as $t) {
                            if ($t->account->getMeta('accountRole') == 'sharedExpense') {
                                echo '#'.$journal->id.' is a shared expense!<br>';
                                return $journal;
                            }
                        }
                    }
                }
            );
        }
        /** @var \TransactionJournal $expense */
        foreach ($expenses as $expense) {
            $sum += $expense->getAmount();
        }


        return $sum;
    }

    /**
     * @param \Account $account
     * @param Carbon   $month
     *
     * @return float
     */
    public function getIncomeByMonth(\Account $account, Carbon $month)
    {
        if (isset($account->sharedExpense) && $account->sharedExpense === true) {
            $shared = true;
        } else {
            if (isset($account->sharedExpense) && $account->sharedExpense === false) {
                $shared = false;
            } else {
                $shared = ($account->getMeta('accountRole') == 'sharedExpense');
            }
        }

        $start = clone $month;
        $end   = clone $month;
        $start->startOfMonth();
        $end->endOfMonth();
        $sum = 0;

        // get all journals.
        $journals = \TransactionJournal::whereIn(
            'id', function ($query) use ($account, $start, $end) {
                $query->select('transaction_journal_id')
                      ->from('transactions')
                      ->where('account_id', $account->id);
            }
        )->before($end)->after($start)->get();


        if ($shared) {
            $incomes = $journals->filter(
                function (\TransactionJournal $journal) use ($account) {
                    // any deposit is an income:
                    if ($journal->transactionType->type == 'Deposit') {
                        return $journal;
                    }
                    // any transfer TO this account is an income.
                    if ($journal->transactionType->type == 'Transfer') {
                        /** @var \Transaction $t */
                        foreach ($journal->transactions as $t) {
                            if ($t->account_id == $account->id && floatval($t->amount) > 0) {
                                return $journal;
                            }
                        }
                    }
                }
            );
        } else {
            $incomes = $journals->filter(
                function (\TransactionJournal $journal) use ($account) {
                    // only deposits are incomes:
                    if ($journal->transactionType->type == 'Deposit') {
                        return $journal;
                    }
                }
            );
        }
        /** @var \TransactionJournal $expense */
        foreach ($incomes as $income) {
            $sum += $income->getAmount();
        }


        return $sum;
    }
}