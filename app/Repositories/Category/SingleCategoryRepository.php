<?php

namespace FireflyIII\Repositories\Category;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Shared\ComponentRepository;
use Illuminate\Support\Collection;

/**
 * Class SingleCategoryRepository
 *
 * @package FireflyIII\Repositories\Category
 */
class SingleCategoryRepository extends ComponentRepository implements SingleCategoryRepositoryInterface
{

    /**
     * @param Category   $category
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return string
     */
    public function balanceInPeriod(Category $category, Carbon $start, Carbon $end, Collection $accounts)
    {
        return $this->commonBalanceInPeriod($category, $start, $end, $accounts);
    }


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
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return int
     */
    public function countJournalsInRange(Category $category, Carbon $start, Carbon $end)
    {
        return $category->transactionJournals()->before($end)->after($start)->count();
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
     * TODO this method is not optimal, and should be replaced.
     *
     * @deprecated
     *
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return string
     */
    public function earnedInPeriod(Category $category, Carbon $start, Carbon $end)
    {
        $sum = $category->transactionjournals()->transactionTypes([TransactionType::DEPOSIT])->before($end)->after($start)->get(['transaction_journals.*'])
                        ->sum(
                            'amount'
                        );

        return $sum;
    }

    /**
     * Returns an array with the following key:value pairs:
     *
     * yyyy-mm-dd:<amount>
     *
     * Where yyyy-mm-dd is the date and <amount> is the money earned using DEPOSITS in the $category
     * from all the users $accounts.
     *
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    public function earnedPerDay(Category $category, Carbon $start, Carbon $end)
    {
        /** @var Collection $query */
        $query = Auth::user()->transactionJournals()
                     ->transactionTypes([TransactionType::DEPOSIT])
                     ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                     ->where('transactions.amount', '>', 0)
                     ->before($end)
                     ->after($start)
                     ->where('category_transaction_journal.category_id', $category->id)
                     ->groupBy('date')->get(['transaction_journals.date as dateFormatted', DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);

        $return = [];
        foreach ($query->toArray() as $entry) {
            $return[$entry['dateFormatted']] = $entry['sum'];
        }

        return $return;
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
     * @param int      $page
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return mixed
     */
    public function getJournalsInRange(Category $category, $page, Carbon $start, Carbon $end)
    {
        $offset = $page > 0 ? $page * 50 : 0;

        return $category->transactionJournals()
                        ->after($start)
                        ->before($end)
                        ->withRelevantData()->take(50)->offset($offset)
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
     * TODO this method is not optimal, and should be replaced.
     *
     * @deprecated
     *
     * @param Category       $category
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     *
     * @return string
     */
    public function spentInPeriod(Category $category, Carbon $start, Carbon $end)
    {
        $sum = $category->transactionjournals()->transactionTypes([TransactionType::WITHDRAWAL])->before($end)->after($start)->get(['transaction_journals.*'])
                        ->sum(
                            'amount'
                        );

        return $sum;
    }


    /**
     * Returns an array with the following key:value pairs:
     *
     * yyyy-mm-dd:<amount>
     *
     * Where yyyy-mm-dd is the date and <amount> is the money spent using DEPOSITS in the $category
     * from all the users accounts.
     *
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    public function spentPerDay(Category $category, Carbon $start, Carbon $end)
    {
        /** @var Collection $query */
        $query = Auth::user()->transactionJournals()
                     ->transactionTypes([TransactionType::WITHDRAWAL])
                     ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                     ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                     ->where('transactions.amount', '<', 0)
                     ->before($end)
                     ->after($start)
                     ->where('category_transaction_journal.category_id', $category->id)
                     ->groupBy('date')->get(['transaction_journals.date as dateFormatted', DB::Raw('SUM(`transactions`.`amount`) AS `sum`')]);

        $return = [];
        foreach ($query->toArray() as $entry) {
            $return[$entry['dateFormatted']] = $entry['sum'];
        }

        return $return;
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