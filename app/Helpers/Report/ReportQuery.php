<?php

namespace FireflyIII\Helpers\Report;

use Auth;
use Carbon\Carbon;
use Crypt;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Steam;

/**
 * Class ReportQuery
 *
 * @package FireflyIII\Helpers\Report
 */
class ReportQuery implements ReportQueryInterface
{
    /**
     * See ReportQueryInterface::incomeInPeriodCorrected
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     *
     */
    public function expenseInPeriodCorrected(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = $this->queryJournalsWithTransactions($start, $end);
        if ($includeShared === false) {
            $query->where(
                function (Builder $query) {
                    $query->where(
                        function (Builder $q) { // only get withdrawals not from a shared account
                            $q->where('transaction_types.type', 'Withdrawal');
                            $q->where('acm_from.data', '!=', '"sharedAsset"');
                        }
                    );
                    $query->orWhere(
                        function (Builder $q) { // and transfers from a shared account.
                            $q->where('transaction_types.type', 'Transfer');
                            $q->where('acm_to.data', '=', '"sharedAsset"');
                        }
                    );
                }
            );
        } else {
            $query->where('transaction_types.type', 'Withdrawal'); // any withdrawal is fine.
        }
        $query->orderBy('transaction_journals.date');

        // get everything
        $data = $query->get(
            ['transaction_journals.*', 'transaction_types.type', 'ac_to.name as name', 'ac_to.id as account_id', 'ac_to.encrypted as account_encrypted']
        );

        $data->each(
            function (TransactionJournal $journal) {
                if (intval($journal->account_encrypted) == 1) {
                    $journal->name = Crypt::decrypt($journal->name);
                }
            }
        );
        $data = $data->filter(
            function (TransactionJournal $journal) {
                if ($journal->amount != 0) {
                    return $journal;
                }
                return null;
            }
        );

        return $data;
    }

    /**
     * Get a users accounts combined with various meta-data related to the start and end date.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function getAllAccounts(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = Auth::user()->accounts()->orderBy('accounts.name', 'ASC')
                     ->accountTypeIn(['Default account', 'Asset account', 'Cash account']);
        if ($includeShared === false) {
            $query->leftJoin(
                'account_meta', function (JoinClause $join) {
                $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
            }
            )
                  ->orderBy('accounts.name', 'ASC')
                  ->where(
                      function (Builder $query) {

                          $query->where('account_meta.data', '!=', '"sharedAsset"');
                          $query->orWhereNull('account_meta.data');

                      }
                  );
        }
        $set = $query->get(['accounts.*']);
        $set->each(
            function (Account $account) use ($start, $end) {
                /**
                 * The balance for today always incorporates transactions
                 * made on today. So to get todays "start" balance, we sub one
                 * day.
                 */
                $yesterday = clone $start;
                $yesterday->subDay();

                /** @noinspection PhpParamsInspection */
                $account->startBalance = Steam::balance($account, $yesterday);
                $account->endBalance   = Steam::balance($account, $end);
            }
        );

        return $set;
    }


    /**
     * This method works the same way as ReportQueryInterface::incomeInPeriod does, but instead of returning results
     * will simply list the transaction journals only. This should allow any follow up counting to be accurate with
     * regards to tags.
     *
     * This method returns all "income" journals in a certain period, which are both transfers from a shared account
     * and "ordinary" deposits. The query used is almost equal to ReportQueryInterface::journalsByRevenueAccount but it does
     * not group and returns different fields.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param bool   $includeShared
     *
     * @return Collection
     */
    public function incomeInPeriodCorrected(Carbon $start, Carbon $end, $includeShared = false)
    {
        $query = $this->queryJournalsWithTransactions($start, $end);
        if ($includeShared === false) {
            // only get deposits not to a shared account
            // and transfers to a shared account.
            $query->where(
                function (Builder $query) {
                    $query->where(
                        function (Builder $q) {
                            $q->where('transaction_types.type', 'Deposit');
                            $q->where('acm_to.data', '!=', '"sharedAsset"');
                        }
                    );
                    $query->orWhere(
                        function (Builder $q) {
                            $q->where('transaction_types.type', 'Transfer');
                            $q->where('acm_from.data', '=', '"sharedAsset"');
                        }
                    );
                }
            );
        } else {
            // any deposit is fine.
            $query->where('transaction_types.type', 'Deposit');
        }
        $query->orderBy('transaction_journals.date');

        // get everything
        $data = $query->get(
            ['transaction_journals.*', 'transaction_types.type', 'ac_from.name as name', 'ac_from.id as account_id', 'ac_from.encrypted as account_encrypted']
        );

        $data->each(
            function (TransactionJournal $journal) {
                if (intval($journal->account_encrypted) == 1) {
                    $journal->name = Crypt::decrypt($journal->name);
                }
            }
        );
        $data = $data->filter(
            function (TransactionJournal $journal) {
                if ($journal->amount != 0) {
                    return $journal;
                }
                return null;
            }
        );

        return $data;
    }

    /**
     * Covers tags
     *
     * @param Account $account
     * @param Budget  $budget
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return float
     */
    public function spentInBudgetCorrected(Account $account, Budget $budget, Carbon $start, Carbon $end)
    {

        return floatval(
                   Auth::user()->transactionjournals()
                       ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                       ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                       ->transactionTypes(['Withdrawal'])
                       ->where('transactions.account_id', $account->id)
                       ->before($end)
                       ->after($start)
                       ->where('budget_transaction_journal.budget_id', $budget->id)
                       ->get(['transaction_journals.*'])->sum('amount')
               ) * -1;
    }

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     * @param bool    $shared
     *
     * @return float
     */
    public function spentNoBudget(Account $account, Carbon $start, Carbon $end, $shared = false)
    {
        return floatval(
            Auth::user()->transactionjournals()
                ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                ->leftJoin('budget_transaction_journal', 'budget_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                ->transactionTypes(['Withdrawal'])
                ->where('transactions.account_id', $account->id)
                ->before($end)
                ->after($start)
                ->whereNull('budget_transaction_journal.budget_id')->get(['transaction_journals.*'])->sum('amount')
        );
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Builder
     */
    protected function queryJournalsWithTransactions(Carbon $start, Carbon $end)
    {
        $query = TransactionJournal::
        leftJoin(
            'transactions as t_from', function (JoinClause $join) {
            $join->on('t_from.transaction_journal_id', '=', 'transaction_journals.id')->where('t_from.amount', '<', 0);
        }
        )
                                   ->leftJoin('accounts as ac_from', 't_from.account_id', '=', 'ac_from.id')
                                   ->leftJoin(
                                       'account_meta as acm_from', function (JoinClause $join) {
                                       $join->on('ac_from.id', '=', 'acm_from.account_id')->where('acm_from.name', '=', 'accountRole');
                                   }
                                   )
                                   ->leftJoin(
                                       'transactions as t_to', function (JoinClause $join) {
                                       $join->on('t_to.transaction_journal_id', '=', 'transaction_journals.id')->where('t_to.amount', '>', 0);
                                   }
                                   )
                                   ->leftJoin('accounts as ac_to', 't_to.account_id', '=', 'ac_to.id')
                                   ->leftJoin(
                                       'account_meta as acm_to', function (JoinClause $join) {
                                       $join->on('ac_to.id', '=', 'acm_to.account_id')->where('acm_to.name', '=', 'accountRole');
                                   }
                                   )
                                   ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
        $query->before($end)->after($start)->where('transaction_journals.user_id', Auth::user()->id);

        return $query;
    }
}
