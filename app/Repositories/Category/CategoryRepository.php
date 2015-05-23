<?php

namespace FireflyIII\Repositories\Category;

use Auth;
use Carbon\Carbon;
use Crypt;
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
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getCategoriesAndExpensesCorrected($start, $end)
    {
        $set = Auth::user()->transactionjournals()
                   ->leftJoin(
                       'category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id'
                   )
                   ->leftJoin('categories', 'categories.id', '=', 'category_transaction_journal.category_id')
                   ->before($end)
                   ->where('categories.user_id', Auth::user()->id)
                   ->after($start)
                   ->transactionTypes(['Withdrawal'])
                   ->groupBy('categories.id')
                   ->get(['categories.id as category_id', 'categories.encrypted as category_encrypted', 'categories.name', 'transaction_journals.*']);

        $result = [];
        foreach ($set as $entry) {
            $categoryId = intval($entry->category_id);
            if (isset($result[$categoryId])) {
                $result[$categoryId]['sum'] += floatval($entry->amount);
            } else {
                $isEncrypted         = intval($entry->category_encrypted) == 1 ? true : false;
                $name                = strlen($entry->name) == 0 ? trans('firefly.noCategory') : $entry->name;
                $name                = $isEncrypted ? Crypt::decrypt($name) : $name;
                $result[$categoryId] = [
                    'name' => $name,
                    'sum'  => floatval($entry->amount),
                ];

            }
        }

        return $result;
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
     * @param bool     $shared
     *
     * @return float
     */
    public function spentInPeriodCorrected(Category $category, Carbon $start, Carbon $end, $shared = false)
    {
        if ($shared === true) {
            // shared is true.
            // always ignore transfers between accounts!
            $sum = floatval(
                $category->transactionjournals()
                         ->transactionTypes(['Withdrawal'])
                         ->before($end)->after($start)->get(['transaction_journals.*'])->sum('amount')
            );

        } else {
            // do something else, SEE budgets.
            // get all journals in this month where the asset account is NOT shared.
            $sum = $category->transactionjournals()
                            ->before($end)
                            ->after($start)
                            ->transactionTypes(['Withdrawal'])
                            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                            ->leftJoin(
                                'account_meta', function (JoinClause $join) {
                                $join->on('account_meta.account_id', '=', 'accounts.id')->where('account_meta.name', '=', 'accountRole');
                            }
                            )
                            ->where('account_meta.data', '!=', '"sharedAsset"')
                            ->get(['transaction_journals.*'])->sum('amount');
            $sum = floatval($sum);
        }

        return $sum;
    }

    /**
     * Corrected for tags
     *
     * @param Category $category
     * @param Carbon   $date
     *
     * @return float
     */
    public function spentOnDaySumCorrected(Category $category, Carbon $date)
    {
        return floatval($category->transactionjournals()->onDate($date)->get(['transaction_journals.*'])->sum('amount'));
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
