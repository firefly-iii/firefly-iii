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
     * SingleCategoryRepository constructor.
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
    public function countJournals(Category $category): int
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
    public function countJournalsInRange(Category $category, Carbon $start, Carbon $end): int
    {
        return $category->transactionjournals()->before($end)->after($start)->count();
    }

    /**
     * @param Category $category
     *
     * @return bool
     */
    public function destroy(Category $category): bool
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
     * @param Category   $category
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function earnedPerDay(Category $category, Carbon $start, Carbon $end, Collection $accounts): array
    {
        /** @var Collection $query */
        $query = $category->transactionjournals()
                          ->expanded()
                          ->transactionTypes([TransactionType::DEPOSIT])
                          ->before($end)
                          ->after($start)
                          ->groupBy('transaction_journals.date');

        if ($accounts->count() > 0) {
            $ids = $accounts->pluck('id')->toArray();
            $query->whereIn('destination_account.id', $ids);
        }

        $result = $query->get(['transaction_journals.date as dateFormatted', DB::raw('SUM(`destination`.`amount`) AS `sum`')]);

        $return = [];
        foreach ($result->toArray() as $entry) {
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
    public function getFirstActivityDate(Category $category): Carbon
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
     * @param int      $pageSize
     *
     * @return Collection
     */
    public function getJournals(Category $category, int $page, int $pageSize = 50): Collection
    {
        $offset = $page > 0 ? $page * $pageSize : 0;

        return $category->transactionjournals()->expanded()->take($pageSize)->offset($offset)
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
    public function getJournalsForAccountsInRange(Category $category, Collection $accounts, Carbon $start, Carbon $end): Collection
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
     * @param Carbon   $start
     * @param Carbon   $end
     * @param int      $page
     * @param int      $pageSize
     *
     *
     * @return Collection
     */
    public function getJournalsInRange(Category $category, Carbon $start, Carbon $end, int $page, int $pageSize = 50): Collection
    {
        $offset = $page > 0 ? $page * $pageSize : 0;

        return $category->transactionjournals()
                        ->after($start)
                        ->before($end)
                        ->expanded()
                        ->take($pageSize)
                        ->offset($offset)
                        ->get(TransactionJournal::QUERYFIELDS);
    }

    /**
     * @param Category $category
     *
     * @return Carbon|null
     */
    public function getLatestActivity(Category $category): Carbon
    {
        $latest = $category->transactionjournals()
                           ->orderBy('transaction_journals.date', 'DESC')
                           ->orderBy('transaction_journals.order', 'ASC')
                           ->orderBy('transaction_journals.id', 'DESC')
                           ->first();
        if ($latest) {
            return $latest->date;
        }

        return new Carbon('1900-01-01');
    }

    /**
     * Returns an array with the following key:value pairs:
     *
     * yyyy-mm-dd:<amount>
     *
     * Where yyyy-mm-dd is the date and <amount> is the money spent using DEPOSITS in the $category
     * from all the users accounts.
     *
     * @param Category   $category
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function spentPerDay(Category $category, Carbon $start, Carbon $end, Collection $accounts): array
    {
        /** @var Collection $query */
        $query = $category->transactionjournals()
                          ->expanded()
                          ->transactionTypes([TransactionType::WITHDRAWAL])
                          ->before($end)
                          ->after($start)
                          ->groupBy('transaction_journals.date');

        if ($accounts->count() > 0) {
            $ids = $accounts->pluck('id')->toArray();
            $query->whereIn('source_account.id', $ids);
        }


        $result = $query->get(['transaction_journals.date as dateFormatted', DB::raw('SUM(`transactions`.`amount`) AS `sum`')]);

        $return = [];
        foreach ($result->toArray() as $entry) {
            $return[$entry['dateFormatted']] = $entry['sum'];
        }

        return $return;
    }

    /**
     * @param array $data
     *
     * @return Category
     */
    public function store(array $data): Category
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
    public function update(Category $category, array $data): Category
    {
        // update the account:
        $category->name = $data['name'];
        $category->save();

        return $category;
    }
}
