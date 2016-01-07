<?php

namespace FireflyIII\Repositories\Category;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionType;
use FireflyIII\Sql\Query;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Class CategoryRepository
 *
 * @package FireflyIII\Repositories\Category
 */
class CategoryRepository implements CategoryRepositoryInterface
{

    /**
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    public function listCategories()
    {
        /** @var Collection $set */
        $set = Auth::user()->categories()->orderBy('name', 'ASC')->get();
        $set = $set->sortBy(
            function (Category $category) {
                return strtolower($category->name);
            }
        );

        return $set;
    }

    /**
     * Returns a list of transaction journals in the range (all types, all accounts) that have no category
     * associated to them.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function listNoCategory(Carbon $start, Carbon $end)
    {
        return Auth::user()
                   ->transactionjournals()
                   ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                   ->whereNull('category_transaction_journal.id')
                   ->before($end)
                   ->after($start)
                   ->orderBy('transaction_journals.date', 'DESC')
                   ->orderBy('transaction_journals.order', 'ASC')
                   ->orderBy('transaction_journals.id', 'DESC')
                   ->get(['transaction_journals.*']);
    }

    /**
     * This method returns a very special collection for each category:
     *
     * category, year, expense/earned, amount
     *
     * categories can be duplicated.
     *
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function listMultiYear(Collection $categories, Collection $accounts, Carbon $start, Carbon $end)
    {
        /*
         * select categories.id, DATE_FORMAT(transaction_journals.date,"%Y") as dateFormatted, transaction_types.type, SUM(amount) as sum from categories

left join category_transaction_journal ON category_transaction_journal.category_id = categories.id
left join transaction_journals ON transaction_journals.id = category_transaction_journal.transaction_journal_id
left join transaction_types ON transaction_types.id = transaction_journals.transaction_type_id
left join transactions ON transactions.transaction_journal_id = transaction_journals.id


where
categories.user_id =1
and transaction_types.type in ("Withdrawal","Deposit")
and transactions.account_id IN (2,4,6,10,11,610,725,879,1248)

group by categories.id, transaction_types.type, dateFormatted
         */
        $set = Auth::user()->categories()
                   ->leftJoin('category_transaction_journal', 'category_transaction_journal.category_id', '=', 'categories.id')
                   ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'category_transaction_journal.transaction_journal_id')
                   ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                   ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                   ->whereIn('transaction_types.type', [TransactionType::DEPOSIT, TransactionType::WITHDRAWAL])
                   ->whereIn('transactions.account_id', $accounts->pluck('id')->toArray())
                   ->whereIn('categories.id', $categories->pluck('id')->toArray())
                   ->groupBy('categories.id')
                   ->groupBy('transaction_types.type')
                   ->groupBy('dateFormatted')
                   ->get(
                       [
                           'categories.*',
                           DB::Raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y") as `dateFormatted`'),
                           'transaction_types.type',
                           DB::Raw('SUM(`amount`) as `sum`')
                       ]
                   );

        return $set;

    }


    /**
     * Returns a collection of Categories appended with the amount of money that has been earned
     * in these categories, based on the $accounts involved, in period X, grouped per month.
     * The amount earned in category X in period X is saved in field "earned".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function earnedForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end)
    {

        $collection = Auth::user()->categories()
                          ->leftJoin('category_transaction_journal', 'category_transaction_journal.category_id', '=', 'categories.id')
                          ->leftJoin('transaction_journals', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                          ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                          ->leftJoin(
                              'transactions AS t_src', function (JoinClause $join) {
                              $join->on('t_src.transaction_journal_id', '=', 'transaction_journals.id')->where('t_src.amount', '<', 0);
                          }
                          )
                          ->leftJoin(
                              'transactions AS t_dest', function (JoinClause $join) {
                              $join->on('t_dest.transaction_journal_id', '=', 'transaction_journals.id')->where('t_dest.amount', '>', 0);
                          }
                          )
                          ->whereIn('t_dest.account_id', $accounts->pluck('id')->toArray())// to these accounts (earned)
                          ->whereNotIn('t_src.account_id', $accounts->pluck('id')->toArray())//-- but not from these accounts
                          ->whereIn(
                'transaction_types.type', [TransactionType::DEPOSIT, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE]
            )
                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                          ->groupBy('categories.id')
                          ->groupBy('dateFormatted')
                          ->get(
                              [
                                  'categories.*',
                                  DB::Raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y-%m") as `dateFormatted`'),
                                  DB::Raw('SUM(`t_dest`.`amount`) AS `earned`')
                              ]
                          );

        return $collection;


    }

    /**
     * Returns a collection of Categories appended with the amount of money that has been spent
     * in these categories, based on the $accounts involved, in period X, grouped per month.
     * The amount spent in category X in period X is saved in field "spent".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function spentForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end)
    {
        $accountIds = $accounts->pluck('id')->toArray();
        $query      = Auth::user()->categories()
                          ->leftJoin('category_transaction_journal', 'category_transaction_journal.category_id', '=', 'categories.id')
                          ->leftJoin('transaction_journals', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                          ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                          ->leftJoin(
                              'transactions AS t_src', function (JoinClause $join) {
                              $join->on('t_src.transaction_journal_id', '=', 'transaction_journals.id')->where('t_src.amount', '<', 0);
                          }
                          )
                          ->leftJoin(
                              'transactions AS t_dest', function (JoinClause $join) {
                              $join->on('t_dest.transaction_journal_id', '=', 'transaction_journals.id')->where('t_dest.amount', '>', 0);
                          }
                          )
                          ->whereIn(
                              'transaction_types.type', [TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE]
                          )// spent on these things.
                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                          ->groupBy('categories.id')
                          ->groupBy('dateFormatted');

        if (count($accountIds) > 0) {
            $query->whereIn('t_src.account_id', $accountIds)// from these accounts (spent)
                  ->whereNotIn('t_dest.account_id', $accountIds);//-- but not from these accounts (spent internally)
        }

        $collection = $query->get(
            [
                'categories.*',
                DB::Raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y-%m") as `dateFormatted`'),
                DB::Raw('SUM(`t_src`.`amount`) AS `spent`')
            ]
        );

        return $collection;
    }


    /**
     * Returns the total amount of money related to transactions without any category connected to
     * it. Returns either the spent amount.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function sumSpentNoCategory(Collection $accounts, Carbon $start, Carbon $end)
    {
        return $this->sumNoCategory($accounts, $start, $end, Query::SPENT);
    }

    /**
     * Returns the total amount of money related to transactions without any category connected to
     * it. Returns either the earned amount.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function sumEarnedNoCategory(Collection $accounts, Carbon $start, Carbon $end)
    {
        return $this->sumNoCategory($accounts, $start, $end, Query::EARNED);
    }

    /**
     * Returns the total amount of money related to transactions without any category connected to
     * it. Returns either the earned or the spent amount.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     * @param int        $group
     *
     * @return string
     */
    protected function sumNoCategory(Collection $accounts, Carbon $start, Carbon $end, $group = Query::EARNED)
    {
        $accountIds = $accounts->pluck('id')->toArray();
        if ($group == Query::EARNED) {
            $types = [TransactionType::DEPOSIT];
        } else {
            $types = [TransactionType::WITHDRAWAL];
        }

        // is withdrawal or transfer AND account_from is in the list of $accounts
        $query = Auth::user()
                     ->transactionjournals()
                     ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                     ->whereNull('category_transaction_journal.id')
                     ->before($end)
                     ->after($start)
                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                     ->transactionTypes($types);
        if (count($accountIds) > 0) {
            $query->whereIn('transactions.account_id', $accountIds);
        }


        $single = $query->first(
            [
                DB::Raw('SUM(`transactions`.`amount`) as `sum`')
            ]
        );
        if (!is_null($single)) {
            return $single->sum;
        }

        return '0';

    }
}
