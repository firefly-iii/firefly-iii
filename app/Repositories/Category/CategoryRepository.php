<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class CategoryRepository
 *
 * @package FireflyIII\Repositories\Category
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * CategoryRepository constructor.
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
     * @return bool
     */
    public function destroy(Category $category): bool
    {
        $category->delete();

        return true;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriod(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): string
    {
        $types = [TransactionType::DEPOSIT, TransactionType::TRANSFER];
        $sum   = bcmul($this->sumInPeriod($categories, $accounts, $types, $start, $end), '-1');

        return $sum;

    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end) :string
    {
        $types = [TransactionType::DEPOSIT, TransactionType::TRANSFER];
        $sum   = $this->sumInPeriodWithoutCategory($accounts, $types, $start, $end);

        return $sum;
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
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon
     */
    public function firstUseDate(Category $category, Collection $accounts): Carbon
    {
        $first = null;

        /** @var TransactionJournal $first */
        $firstJournalQuery = $category->transactionjournals()->orderBy('date', 'ASC');

        if ($accounts->count() > 0) {
            // filter journals:
            $ids = $accounts->pluck('id')->toArray();
            $firstJournalQuery->leftJoin('transactions as t', 't.transaction_journal_id', '=', 'transaction_journals.id');
            $firstJournalQuery->whereIn('t.account_id', $ids);
        }

        $firstJournal = $firstJournalQuery->first(['transaction_journals.date']);

        if ($firstJournal) {
            $first = $firstJournal->date;
        }

        // check transactions:

        $firstTransactionQuery = $category->transactions()
                                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                          ->orderBy('transaction_journals.date', 'ASC');
        if ($accounts->count() > 0) {
            // filter journals:
            $ids = $accounts->pluck('id')->toArray();
            $firstTransactionQuery->whereIn('transactions.account_id', $ids);
        }

        $firstTransaction = $firstTransactionQuery->first(['transaction_journals.date']);

        if (!is_null($firstTransaction) && ((!is_null($first) && $firstTransaction->date < $first) || is_null($first))) {
            $first = new Carbon($firstTransaction->date);
        }
        if (is_null($first)) {
            return new Carbon('1900-01-01');
        }

        return $first;
    }

    /**
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    public function getCategories(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->categories()->orderBy('name', 'ASC')->get();
        $set = $set->sortBy(
            function (Category $category) {
                return strtolower($category->name);
            }
        );

        return $set;
    }

    /**
     * @param Category $category
     * @param int      $page
     * @param int      $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getJournals(Category $category, int $page, int $pageSize): LengthAwarePaginator
    {
        $complete = new Collection;
        // first collect actual transaction journals (fairly easy)
        $query = $this->user->transactionjournals()->expanded()->sortCorrectly();
        $query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
        $query->where('category_transaction_journal.category_id', $category->id);
        $first = $query->get(TransactionJournal::queryFields());

        // then collection transactions (harder)
        $query  = $this->user->transactionjournals()->distinct()
                             ->leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                             ->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id')
                             ->where('category_transaction.category_id', $category->id);
        $second = $query->get(['transaction_journals.*']);

        $complete = $complete->merge($first);
        $complete = $complete->merge($second);

        // sort:
        /** @var Collection $complete */
        $complete = $complete->sortByDesc(
            function ($model) {
                $date = new Carbon($model->date);

                return intval($date->format('U'));
            }
        );
        // create paginator
        $offset    = ($page - 1) * $pageSize;
        $subSet    = $complete->slice($offset, $pageSize)->all();
        $paginator = new LengthAwarePaginator($subSet, $complete->count(), $pageSize, $page);

        return $paginator;
    }

    /**
     * Get all transactions in a category in a range.
     *
     * @param Collection $categories
     * @param Collection $accounts
     * @param array      $types
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriod(Collection $categories, Collection $accounts, array $types, Carbon $start, Carbon $end): Collection
    {
        $complete = new Collection;
        // first collect actual transaction journals (fairly easy)
        $query = $this->user->transactionjournals()->expanded()->sortCorrectly();

        if ($end >= $start) {
            $query->before($end)->after($start);
        }

        if (count($types) > 0) {
            $query->transactionTypes($types);
        }
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->leftJoin('transactions as t', 't.transaction_journal_id', '=', 'transaction_journals.id');
            $query->whereIn('t.account_id', $accountIds);
        }
        if ($categories->count() > 0) {
            $categoryIds = $categories->pluck('id')->toArray();
            $query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $query->whereIn('category_transaction_journal.category_id', $categoryIds);
        }

        // that should do it:
        $first = $query->get(TransactionJournal::queryFields());

        
        // then collection transactions (harder)
        $query  = $this->user->transactionjournals()->distinct()
                             ->leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                             ->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');

        if (count($types) > 0) {
            $query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
            $query->whereIn('transaction_types.type', $types);
        }
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->whereIn('transactions.account_id', $accountIds);
        }
        if ($categories->count() > 0) {
            $categoryIds = $categories->pluck('id')->toArray();
            $query->whereIn('category_transaction.category_id', $categoryIds);
        }


        $second = $query->get(['transaction_journals.*']);

        $complete = $complete->merge($first);
        $complete = $complete->merge($second);

        return $complete;
    }

    /**
     * @param Collection $accounts
     * @param  array     $types
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriodWithoutCategory(Collection $accounts, array $types, Carbon $start, Carbon $end) : Collection
    {
        /** @var Collection $set */
        $query = $this->user
            ->transactionjournals();
        if (count($types) > 0) {
            $query->transactionTypes($types);
        }

        $query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
              ->whereNull('category_transaction_journal.id')
              ->before($end)
              ->after($start);

        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->leftJoin('transactions as t', 't.transaction_journal_id', '=', 'transaction_journals.id');
            $query->whereIn('t.account_id', $accountIds);
        }

        $set = $query->get(['transaction_journals.*']);

        if ($set->count() == 0) {
            return new Collection;
        }

        // grab all the transactions from this set.
        // take only the journals with transactions that all have no category.
        // select transactions left join journals where id in this set
        // and left join transaction-category where null category
        $journalIds  = $set->pluck('id')->toArray();
        $secondQuery = $this->user->transactions()
                                  ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transaction.transaction_journal_id')
                                  ->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id')
                                  ->whereNull('category_transaction.id')
                                  ->whereIn('transaction_journals.id', $journalIds);

        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $secondQuery->whereIn('transactions.account_id', $accountIds);
        }

        // this second set REALLY doesn't have any categories.
        $secondSet = $secondQuery->get(['transactions.transaction_journal_id']);
        $allIds    = $secondSet->pluck('transaction_journal_id')->toArray();
        $return    = $this->user->transactionjournals()->sortCorrectly()->expanded()->whereIn('transaction_journals.id', $allIds)->get(
            TransactionJournal::queryFields()
        );

        return $return;


    }

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon
     */
    public function lastUseDate(Category $category, Collection $accounts): Carbon
    {
        $last = null;

        /** @var TransactionJournal $first */
        $lastJournalQuery = $category->transactionjournals()->orderBy('date', 'DESC');

        if ($accounts->count() > 0) {
            // filter journals:
            $ids = $accounts->pluck('id')->toArray();
            $lastJournalQuery->leftJoin('transactions as t', 't.transaction_journal_id', '=', 'transaction_journals.id');
            $lastJournalQuery->whereIn('t.account_id', $ids);
        }

        $lastJournal = $lastJournalQuery->first(['transaction_journals.*']);

        if ($lastJournal) {
            $last = $lastJournal->date;
        }

        // check transactions:

        $lastTransactionQuery = $category->transactions()
                                         ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                         ->orderBy('transaction_journals.date', 'DESC');
        if ($accounts->count() > 0) {
            // filter journals:
            $ids = $accounts->pluck('id')->toArray();
            $lastTransactionQuery->whereIn('transactions.account_id', $ids);
        }

        $lastTransaction = $lastTransactionQuery->first(['transaction_journals.*']);
        if (!is_null($lastTransaction) && ((!is_null($last) && $lastTransaction->date < $last) || is_null($last))) {
            $last = new Carbon($lastTransaction->date);
        }

        if (is_null($last)) {
            return new Carbon('1900-01-01');
        }

        return $last;
    }

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): string
    {
        $types = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
        $sum   = $this->sumInPeriod($categories, $accounts, $types, $start, $end);

        return $sum;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end) : string
    {
        $types = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
        $sum   = $this->sumInPeriodWithoutCategory($accounts, $types, $start, $end);

        return $sum;
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

    /**
     * @param Collection $categories
     * @param Collection $accounts
     * @param array      $types
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    private function sumInPeriod(Collection $categories, Collection $accounts, array $types, Carbon $start, Carbon $end): string
    {
        // first collect actual transaction journals (fairly easy)
        $query = $this->user
            ->transactionjournals()
            ->transactionTypes($types)
            ->leftJoin(
                'transactions as source', function (JoinClause $join) {
                $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', 0);
            }
            )
            ->leftJoin(
                'transactions as destination', function (JoinClause $join) {
                $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', 0);
            }
            );

        if ($end >= $start) {
            $query->before($end)->after($start);
        }
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $set        = join(', ', $accountIds);
            $query->whereRaw('(source.account_id in (' . $set . ') XOR destination.account_id in (' . $set . '))');

        }
        if ($categories->count() > 0) {
            $categoryIds = $categories->pluck('id')->toArray();
            $query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
            $query->whereIn('category_transaction_journal.category_id', $categoryIds);
        }

        // that should do it:
        $first = strval($query->sum('source.amount'));

        // then collection transactions (harder)
        $query = $this->user->transactions()
                            ->where('transactions.amount', '<', 0)
                            ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
                            ->where('transaction_journals.date', '<=', $end->format('Y-m-d 23:59:59'));
        if (count($types) > 0) {
            $query->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id');
            $query->whereIn('transaction_types.type', $types);
        }
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->whereIn('transactions.account_id', $accountIds);
        }
        if ($categories->count() > 0) {
            $categoryIds = $categories->pluck('id')->toArray();
            $query->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');
            $query->whereIn('category_transaction.category_id', $categoryIds);
        }
        $second = strval($query->sum('transactions.amount'));

        return bcadd($first, $second);

    }

    /**
     * @param Collection $accounts
     * @param array      $types
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    private function sumInPeriodWithoutCategory(Collection $accounts, array $types, Carbon $start, Carbon $end): string
    {
        $query = $this->user->transactionjournals()
                            ->distinct()
                            ->transactionTypes($types)
                            ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
                            ->leftJoin(
                                'transactions as t', function (JoinClause $join) {
                                $join->on('t.transaction_journal_id', '=', 'transaction_journals.id')->where('amount', '<', 0);
                            }
                            )
                            ->leftJoin('category_transaction', 't.id', '=', 'category_transaction.transaction_id')
                            ->whereNull('category_transaction_journal.id')
                            ->whereNull('category_transaction.id')
                            ->before($end)
                            ->after($start);

        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();

            $query->whereIn('t.account_id', $accountIds);
        }
        $sum = strval($query->sum('t.amount'));

        return $sum;

    }
}
