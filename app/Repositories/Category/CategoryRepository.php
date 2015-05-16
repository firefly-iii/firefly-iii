<?php

namespace FireflyIII\Repositories\Category;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
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
     * @param Category $category
     *
     * @return int
     */
    public function countJournals(Category $category)
    {
        return $category->transactionJournals()->count();

    }

    /**
     * @param Category $category
     *
     * @return boolean
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return true;
    }

    /**
     * @return Collection
     */
    public function getCategories()
    {
        /** @var Collection $set */
        $set = Auth::user()->categories()->orderBy('name', 'ASC')->get();
        $set->sortBy(
            function (Category $category) {
                return $category->name;
            }
        );

        return $set;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getCategoriesAndExpenses($start, $end)
    {
        return TransactionJournal::
        where('transaction_journals.user_id', Auth::user()->id)
                                 ->leftJoin(
                                     'transactions',
                                     function (JoinClause $join) {
                                         $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('amount', '>', 0);
                                     }
                                 )
                                 ->leftJoin(
                                     'category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id'
                                 )
                                 ->leftJoin('categories', 'categories.id', '=', 'category_transaction_journal.category_id')
                                 ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                 ->before($end)
                                 ->where('categories.user_id', Auth::user()->id)
                                 ->after($start)
                                 ->where('transaction_types.type', 'Withdrawal')
                                 ->groupBy('categories.id')
                                 ->orderBy('sum', 'DESC')
                                 ->get(['categories.id', 'categories.encrypted', 'categories.name', DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);
    }

    /**
     * @param Category $category
     *
     * @return Carbon
     */
    public function getFirstActivityDate(Category $category)
    {
        /** @var TransactionJournal $first */
        $first = $category->transactionjournals()->orderBy('date', 'ASC')->first();
        if ($first) {
            return $first->date;
        }

        return new Carbon;

    }

    /**
     * @param Category $category
     * @param int      $page
     *
     * @return Collection
     */
    public function getJournals(Category $category, $page)
    {
        $offset = $page > 0 ? $page * 50 : 0;

        return $category->transactionJournals()->withRelevantData()->take(50)->offset($offset)
                        ->orderBy('transaction_journals.date', 'DESC')
                        ->orderBy('transaction_journals.order', 'ASC')
                        ->orderBy('transaction_journals.id', 'DESC')
                        ->get(
                            ['transaction_journals.*']
                        );

    }

    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    public function getLatestActivity(Category $category)
    {
        $latest = $category->transactionjournals()
                           ->orderBy('transaction_journals.date', 'DESC')
                           ->orderBy('transaction_journals.order', 'ASC')
                           ->orderBy('transaction_journals.id', 'DESC')
                           ->first();
        if ($latest) {
            return $latest->date;
        }

        return null;
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
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return float
     */
    public function spentInPeriod(Category $category, Carbon $start, Carbon $end, $shared = false)
    {
        if ($shared === true) {
            // shared is true.
            // always ignore transfers between accounts!
            $sum = floatval(
                       $category->transactionjournals()
                                ->transactionTypes(['Withdrawal', 'Deposit'])
                                ->before($end)->after($start)->lessThan(0)->sum('amount')
                   ) * -1;

        } else {
            // do something else, SEE budgets.
            // get all journals in this month where the asset account is NOT shared.
            $sum = $category->transactionjournals()
                            ->before($end)
                            ->after($start)
                            ->transactionTypes(['Withdrawal', 'Deposit'])
                            ->lessThan(0)
                            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                            ->leftJoin(
                                'account_meta', function (JoinClause $join) {
                                $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                            }
                            )
                            ->where('account_meta.data', '!=', '"sharedAsset"')
                            ->sum('amount');
            $sum = floatval($sum) * -1;
        }

        return $sum;
    }

    /**
     * @param Category $category
     * @param Carbon   $date
     *
     * @return float
     */
    public function spentOnDaySum(Category $category, Carbon $date)
    {
        return floatval($category->transactionjournals()->onDate($date)->lessThan(0)->sum('amount')) * -1;
    }

    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data)
    {
        $newCategory = new Category(
            [
                'user_id' => $data['user'],
                'name'    => $data['name'],
            ]
        );
        $newCategory->save();

        return $newCategory;
    }

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     */
    public function update(Category $category, array $data)
    {
        // update the account:
        $category->name = $data['name'];
        $category->save();

        return $category;
    }
}
