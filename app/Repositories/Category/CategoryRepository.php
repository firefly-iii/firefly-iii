<?php

namespace FireflyIII\Repositories\Category;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionType;
use FireflyIII\Sql\Query;
use FireflyIII\Support\CacheProperties;
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
     * @return Collection
     */
    public function getCategories()
    {
        $cache = new CacheProperties;
        $cache->addProperty('category-list');

        if ($cache->has()) {
            return $cache->get();
        }

        /** @var Collection $set */
        $set = Auth::user()->categories()->orderBy('name', 'ASC')->get();
        $set = $set->sortBy(
            function (Category $category) {
                return strtolower($category->name);
            }
        );

        $cache->store($set);

        return $set;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return array
     */
    public function getCategoriesAndExpenses(Carbon $start, Carbon $end)
    {
        $set   = Auth::user()->categories()
                     ->leftJoin('category_transaction_journal', 'category_transaction_journal.category_id', '=', 'categories.id')
                     ->leftJoin('transaction_journals', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                     ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                     ->leftJoin(
                         'transactions', function (JoinClause $join) {
                         $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
                     }
                     )
                     ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                     ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                     ->whereIn('transaction_types.type', [TransactionType::WITHDRAWAL])
                     ->whereNull('transaction_journals.deleted_at')
                     ->groupBy('categories.id')
                     ->orderBy('totalAmount')
                     ->get(
                         [
                             'categories.*',
                             DB::Raw('SUM(`transactions`.`amount`) as `totalAmount`')
                         ]
                     );
        $array = [];
        /** @var Category $entry */
        foreach ($set as $entry) {
            $id         = $entry->id;
            $array[$id] = ['name' => $entry->name, 'sum' => $entry->totalAmount];
        }

        // without category:
        // TODO REMOVE ME
        $single     = Auth::user()->transactionjournals()
                          ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                          ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                          ->leftJoin(
                              'transactions', function (JoinClause $join) {
                              $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('transactions.amount', '<', 0);
                          }
                          )
                          ->whereNull('category_transaction_journal.id')
                          ->whereNull('transaction_journals.deleted_at')
                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                          ->whereIn('transaction_types.type', [TransactionType::WITHDRAWAL])
                          ->whereNull('transaction_journals.deleted_at')
                          ->first([DB::Raw('SUM(transactions.amount) as `totalAmount`')]);
        $noCategory = is_null($single->totalAmount) ? '0' : $single->totalAmount;
        $array[0]   = ['name' => trans('firefly.no_category'), 'sum' => $noCategory];

        return $array;

    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getWithoutCategory(Carbon $start, Carbon $end)
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
        $accountIds = [];
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }


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
                          ->whereIn('t_dest.account_id', $accountIds)// to these accounts (earned)
                          ->whereNotIn('t_src.account_id', $accountIds)//-- but not from these accounts
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
     * Returns a collection of Categories appended with the amount of money that has been earned
     * in these categories, based on the $accounts involved, in period X.
     * The amount earned in category X in period X is saved in field "earned".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function earnedForAccounts(Collection $accounts, Carbon $start, Carbon $end)
    {
        $accountIds = [];
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }


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
                          ->whereIn('t_dest.account_id', $accountIds)// to these accounts (earned)
                          ->whereNotIn('t_src.account_id', $accountIds)//-- but not from these accounts
                          ->whereIn(
                'transaction_types.type', [TransactionType::DEPOSIT, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE]
            )
                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                          ->groupBy('categories.id')
                          ->get(['categories.*', DB::Raw('SUM(`t_dest`.`amount`) AS `earned`')]);

        return $collection;


    }

    /**
     * Returns a collection of Categories appended with the amount of money that has been spent
     * in these categories, based on the $accounts involved, in period X.
     * The amount earned in category X in period X is saved in field "spent".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function spentForAccounts(Collection $accounts, Carbon $start, Carbon $end)
    {
        $accountIds = [];
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }


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
                          ->whereIn('t_src.account_id', $accountIds)// from these accounts (spent)
                          ->whereNotIn('t_dest.account_id', $accountIds)//-- but not from these accounts (spent internally)
                          ->whereIn(
                'transaction_types.type', [TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE]
            )// spent on these things.
                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                          ->groupBy('categories.id')
                          ->get(['categories.*', DB::Raw('SUM(`t_dest`.`amount`) AS `spent`')]);

        return $collection;
    }

    /**
     * Returns a collection of Categories appended with the amount of money that has been spent
     * in these categories, based on the $accounts involved, in period X, grouped per month.
     * The amount earned in category X in period X is saved in field "spent".
     *
     * @param $accounts
     * @param $start
     * @param $end
     *
     * @return Collection
     */
    public function spentForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end)
    {
        $accountIds = [];
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }


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
                          ->whereIn('t_src.account_id', $accountIds)// from these accounts (spent)
                          ->whereNotIn('t_dest.account_id', $accountIds)//-- but not from these accounts (spent internally)
                          ->whereIn(
                'transaction_types.type', [TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE]
            )// spent on these things.
                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
                          ->groupBy('categories.id')
                          ->groupBy('dateFormatted')
                          ->get(
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
        $accountIds = [];
        foreach ($accounts as $account) {
            $accountIds[] = $account->id;
        }
        if ($group == Query::EARNED) {
            $types = [TransactionType::DEPOSIT];
        } else {
            $types = [TransactionType::WITHDRAWAL];
        }

        // is withdrawal or transfer AND account_from is in the list of $accounts
        $single = Auth::user()
                      ->transactionjournals()
                      ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                      ->whereNull('category_transaction_journal.id')
                      ->before($end)
                      ->after($start)
                      ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                      ->whereIn('transactions.account_id', $accountIds)
                      ->transactionTypes($types)
                      ->first(
                          [
                              DB::Raw('SUM(`transactions`.`amount`) as `sum`)')]
                      );
        if (!is_null($single)) {
            return $single->sum;
        }

        return '0';

    }
}
