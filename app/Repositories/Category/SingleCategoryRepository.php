<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Shared\ComponentRepository;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class SingleCategoryRepository
 *
 * @package FireflyIII\Repositories\Category
 */
class SingleCategoryRepository extends ComponentRepository implements SingleCategoryRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * BillRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Category $category
     *
     * @return int
     */
    public function countJournals(Category $category)
    {
        return $category->transactionjournals()->count();

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
        return $category->transactionjournals()->before($end)->after($start)->count();
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
        $query = $category->transactionjournals()
                          ->transactionTypes([TransactionType::DEPOSIT])
                          ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                          ->where('transactions.amount', '>', 0)
                          ->before($end)
                          ->after($start)
                          ->groupBy('date')->get(['transaction_journals.date as dateFormatted', DB::raw('SUM(`transactions`.`amount`) AS `sum`')]);

        $return = [];
        foreach ($query->toArray() as $entry) {
            $return[$entry['dateFormatted']] = $entry['sum'];
        }

        return $return;
    }

    /**
     * Find a category
     *
     * @param int $categoryId
     *
     * @return Category
     */
    public function find(int $categoryId) : Category
    {
        $category = $this->user->categories()->find($categoryId);
        if (is_null($category)) {
            $category = new Category;
        }

        return $category;
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

        return $category->transactionjournals()->expanded()->take(50)->offset($offset)
                        ->orderBy('transaction_journals.date', 'DESC')
                        ->orderBy('transaction_journals.order', 'ASC')
                        ->orderBy('transaction_journals.id', 'DESC')
                        ->get(TransactionJournal::QUERYFIELDS);

    }

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function getJournalsForAccountsInRange(Category $category, Collection $accounts, Carbon $start, Carbon $end)
    {
        $ids = $accounts->pluck('id')->toArray();

        return $category->transactionjournals()
                        ->after($start)
                        ->before($end)
                        ->expanded()
                        ->whereIn('source_account.id', $ids)
                        ->whereNotIn('destination_account.id', $ids)
                        ->get(TransactionJournal::QUERYFIELDS);
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

        return $category->transactionjournals()
                        ->after($start)
                        ->before($end)
                        ->expanded()
                        ->take(50)
                        ->offset($offset)
                        ->get(TransactionJournal::QUERYFIELDS);
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
        $query = $category->transactionjournals()
                          ->transactionTypes([TransactionType::WITHDRAWAL])
                          ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                          ->where('transactions.amount', '<', 0)
                          ->before($end)
                          ->after($start)
                          ->groupBy('date')->get(['transaction_journals.date as dateFormatted', DB::raw('SUM(`transactions`.`amount`) AS `sum`')]);

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
        $newCategory = Category::firstOrCreateEncrypted(
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
