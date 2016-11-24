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
use DB;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Query\JoinClause;
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
        $sum = $this->sumInPeriod($categories, $accounts, TransactionType::DEPOSIT, $start, $end);

        return $sum;

    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function earnedInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end): string
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
    public function find(int $categoryId): Category
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
    public function findByName(string $name): Category
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
        $sum = $this->sumInPeriod($categories, $accounts, TransactionType::WITHDRAWAL, $start, $end);

        return $sum;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end): string
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
     * @param string     $type
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    private function sumInPeriod(Collection $categories, Collection $accounts, string $type, Carbon $start, Carbon $end): string
    {
        $categoryIds = $categories->pluck('id')->toArray();
        $query       = $this->user
            ->transactionJournals()
            ->leftJoin( // join source transaction
                'transactions as source_transactions', function (JoinClause $join) {
                $join->on('source_transactions.transaction_journal_id', '=', 'transaction_journals.id')
                     ->where('source_transactions.amount', '<', 0);

            }
            )
            ->leftJoin( // join destination transaction (slighly more complex)
                'transactions as destination_transactions', function (JoinClause $join) {
                $join->on('destination_transactions.transaction_journal_id', '=', 'transaction_journals.id')
                     ->where('destination_transactions.amount', '>', 0)
                     ->where('destination_transactions.identifier', '=', DB::raw('source_transactions.identifier'));
            }
            )
            // left join source category:
            ->leftJoin('category_transaction as source_cat_trans', 'source_transactions.id', '=', 'source_cat_trans.transaction_id')
            // left join destination category:
            ->leftJoin('category_transaction as dest_cat_trans', 'source_transactions.id', '=', 'dest_cat_trans.transaction_id')
            // left join journal category:
            ->leftJoin('category_transaction_journal as journal_category', 'journal_category.transaction_journal_id', '=', 'transaction_journals.id')
            // left join transaction type:
            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            // where nothing is deleted:
            ->whereNull('transaction_journals.deleted_at')
            ->whereNull('source_transactions.deleted_at')
            ->whereNull('destination_transactions.deleted_at')
            // in correct date range:
            ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
            ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
            // correct categories (complex)
            ->where(
                function ($q1) use ($categoryIds) {
                    $q1->where(
                        function ($q2) use ($categoryIds) {
                            // source and destination transaction have categories, journal does not.
                            $q2->whereIn('source_cat_trans.category_id', $categoryIds);
                            $q2->whereIn('dest_cat_trans.category_id', $categoryIds);
                            $q2->whereNull('journal_category.category_id');
                        }
                    );
                    $q1->orWhere(
                        function ($q3) use ($categoryIds) {
                            // journal has category, source and destination have not
                            $q3->whereNull('source_cat_trans.category_id');
                            $q3->whereNull('dest_cat_trans.category_id');
                            $q3->whereIn('journal_category.category_id', $categoryIds);
                        }
                    );
                }
            )
            // type:
            ->where('transaction_types.type', $type);
        // accounts, if present:
        if ($accounts->count() > 0) {
            $accountIds = $accounts->pluck('id')->toArray();
            $query->where(
                function ($q) use ($accountIds) {
                    $q->whereIn('source_transactions.account_id', $accountIds);
                    $q->orWhereIn('destination_transactions.account_id', $accountIds);
                }
            );
        }
        $sum = strval($query->sum('destination_transactions.amount'));
        if ($sum === '') {
            $sum = '0';
        }


        return $sum;
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
