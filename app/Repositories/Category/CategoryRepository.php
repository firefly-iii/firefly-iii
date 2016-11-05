<?php
/**
 * CategoryRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
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
     * Find a category
     *
     * @param string $name
     *
     * @return Category
     */
    public function findByName(string $name) : Category
    {
        $categories = $this->user->categories()->get(['categories.*']);
        foreach ($categories as $category) {
            if ($category->name === $name) {
                return $category;
            }
        }

        return new Category;
    }

    /**
     * @param Category $category
     *
     * @return Carbon
     */
    public function firstUseDate(Category $category): Carbon
    {
        $first = null;


        /** @var TransactionJournal $first */
        $firstJournal = $category->transactionJournals()->orderBy('date', 'ASC')->first(['transaction_journals.date']);

        if ($firstJournal) {
            $first = $firstJournal->date;
        }

        // check transactions:

        $firstTransaction = $category->transactions()
                                     ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                     ->orderBy('transaction_journals.date', 'ASC')->first(['transaction_journals.date']);

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
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return Carbon
     */
    public function lastUseDate(Category $category, Collection $accounts): Carbon
    {
        $last = null;

        /** @var TransactionJournal $first */
        $lastJournalQuery = $category->transactionJournals()->orderBy('date', 'DESC');

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
                'user_id' => $this->user->id,
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
            ->transactionJournals()
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
            $query->where(
            // source.account_id in accountIds XOR destination.account_id in accountIds
                function (Builder $query) use ($accountIds) {
                    $query->where(
                        function (Builder $q1) use ($accountIds) {
                            $q1->whereIn('source.account_id', $accountIds)
                               ->whereNotIn('destination.account_id', $accountIds);
                        }
                    )->orWhere(
                        function (Builder $q2) use ($accountIds) {
                            $q2->whereIn('destination.account_id', $accountIds)
                               ->whereNotIn('source.account_id', $accountIds);
                        }
                    );
                }
            );
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
        $query = $this->user->transactionJournals()
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
