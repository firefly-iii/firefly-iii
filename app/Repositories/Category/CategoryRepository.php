<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Category;

use Carbon\Carbon;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class CategoryRepository
 *
 * @package FireflyIII\Repositories\Category
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    //    const SPENT  = 1;
    //    const EARNED = 2;


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

    //    /**
    //     * Returns a collection of Categories appended with the amount of money that has been earned
    //     * in these categories, based on the $accounts involved, in period X, grouped per month.
    //     * The amount earned in category X in period X is saved in field "earned".
    //     *
    //     * @param $accounts
    //     * @param $start
    //     * @param $end
    //     *
    //     * @return Collection
    //     */
    //    public function earnedForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end): Collection
    //    {
    //
    //        $collection = $this->user->categories()
    //                                 ->leftJoin('category_transaction_journal', 'category_transaction_journal.category_id', '=', 'categories.id')
    //                                 ->leftJoin('transaction_journals', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
    //                                 ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
    //                                 ->leftJoin(
    //                                     'transactions AS t_src', function (JoinClause $join) {
    //                                     $join->on('t_src.transaction_journal_id', '=', 'transaction_journals.id')->where('t_src.amount', '<', 0);
    //                                 }
    //                                 )
    //                                 ->leftJoin(
    //                                     'transactions AS t_dest', function (JoinClause $join) {
    //                                     $join->on('t_dest.transaction_journal_id', '=', 'transaction_journals.id')->where('t_dest.amount', '>', 0);
    //                                 }
    //                                 )
    //                                 ->whereIn('t_dest.account_id', $accounts->pluck('id')->toArray())// to these accounts (earned)
    //                                 ->whereNotIn('t_src.account_id', $accounts->pluck('id')->toArray())//-- but not from these accounts
    //                                 ->whereIn(
    //                'transaction_types.type', [TransactionType::DEPOSIT, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE]
    //            )
    //                                 ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
    //                                 ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
    //                                 ->groupBy('categories.id')
    //                                 ->groupBy('dateFormatted')
    //                                 ->get(
    //                                     [
    //                                         'categories.*',
    //                                         DB::raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y-%m") as `dateFormatted`'),
    //                                         DB::raw('SUM(`t_dest`.`amount`) AS `earned`'),
    //                                     ]
    //                                 );
    //
    //        return $collection;
    //
    //
    //    }

    //    /**
    //     * @param Category    $category
    //     * @param Carbon|null $start
    //     * @param Carbon|null $end
    //     *
    //     * @return int
    //     */
    //    public function countJournals(Category $category, Carbon $start = null, Carbon $end = null): int
    //    {
    //        $query = $category->transactionjournals();
    //        if (!is_null($start)) {
    //            $query->after($start);
    //        }
    //        if (!is_null($end)) {
    //            $query->before($end);
    //        }
    //
    //        return $query->count();
    //
    //    }

    //    /**
    //     * This method returns a very special collection for each category:
    //     *
    //     * category, year, expense/earned, amount
    //     *
    //     * categories can be duplicated.
    //     *
    //     * @param Collection $categories
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return Collection
    //     */
    //    public function listMultiYear(Collection $categories, Collection $accounts, Carbon $start, Carbon $end): Collection
    //    {
    //
    //        $set = $this->user->categories()
    //                          ->leftJoin('category_transaction_journal', 'category_transaction_journal.category_id', '=', 'categories.id')
    //                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'category_transaction_journal.transaction_journal_id')
    //                          ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
    //                          ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
    //                          ->whereIn('transaction_types.type', [TransactionType::DEPOSIT, TransactionType::WITHDRAWAL])
    //                          ->whereIn('transactions.account_id', $accounts->pluck('id')->toArray())
    //                          ->whereIn('categories.id', $categories->pluck('id')->toArray())
    //                          ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
    //                          ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
    //                          ->groupBy('categories.id')
    //                          ->groupBy('transaction_types.type')
    //                          ->groupBy('dateFormatted')
    //                          ->get(
    //                              [
    //                                  'categories.*',
    //                                  DB::raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y") as `dateFormatted`'),
    //                                  'transaction_types.type',
    //                                  DB::raw('SUM(`amount`) as `sum`'),
    //                              ]
    //                          );
    //
    //        return $set;
    //
    //    }

    //    /**
    //     * Returns a list of transaction journals in the range (all types, all accounts) that have no category
    //     * associated to them.
    //     *
    //     * @param Carbon $start
    //     * @param Carbon $end
    //     *
    //     * @return Collection
    //     */
    //    public function listNoCategory(Carbon $start, Carbon $end): Collection
    //    {
    //        return $this->user
    //            ->transactionjournals()
    //            ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
    //            ->whereNull('category_transaction_journal.id')
    //            ->before($end)
    //            ->after($start)
    //            ->orderBy('transaction_journals.date', 'DESC')
    //            ->orderBy('transaction_journals.order', 'ASC')
    //            ->orderBy('transaction_journals.id', 'DESC')
    //            ->get(['transaction_journals.*']);
    //    }

    //    /**
    //     * Returns a collection of Categories appended with the amount of money that has been spent
    //     * in these categories, based on the $accounts involved, in period X, grouped per month.
    //     * The amount spent in category X in period X is saved in field "spent".
    //     *
    //     * @param $accounts
    //     * @param $start
    //     * @param $end
    //     *
    //     * @return Collection
    //     */
    //    public function spentForAccountsPerMonth(Collection $accounts, Carbon $start, Carbon $end): Collection
    //    {
    //        $accountIds = $accounts->pluck('id')->toArray();
    //        $query      = $this->user->categories()
    //                                 ->leftJoin('category_transaction_journal', 'category_transaction_journal.category_id', '=', 'categories.id')
    //                                 ->leftJoin('transaction_journals', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
    //                                 ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
    //                                 ->leftJoin(
    //                                     'transactions AS t_src', function (JoinClause $join) {
    //                                     $join->on('t_src.transaction_journal_id', '=', 'transaction_journals.id')->where('t_src.amount', '<', 0);
    //                                 }
    //                                 )
    //                                 ->leftJoin(
    //                                     'transactions AS t_dest', function (JoinClause $join) {
    //                                     $join->on('t_dest.transaction_journal_id', '=', 'transaction_journals.id')->where('t_dest.amount', '>', 0);
    //                                 }
    //                                 )
    //                                 ->whereIn(
    //                                     'transaction_types.type', [TransactionType::WITHDRAWAL, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE]
    //                                 )// spent on these things.
    //                                 ->where('transaction_journals.date', '>=', $start->format('Y-m-d'))
    //                                 ->where('transaction_journals.date', '<=', $end->format('Y-m-d'))
    //                                 ->groupBy('categories.id')
    //                                 ->groupBy('dateFormatted');
    //
    //        if (count($accountIds) > 0) {
    //            $query->whereIn('t_src.account_id', $accountIds)// from these accounts (spent)
    //                  ->whereNotIn('t_dest.account_id', $accountIds);//-- but not from these accounts (spent internally)
    //        }
    //
    //        $collection = $query->get(
    //            [
    //                'categories.*',
    //                DB::raw('DATE_FORMAT(`transaction_journals`.`date`,"%Y-%m") as `dateFormatted`'),
    //                DB::raw('SUM(`t_src`.`amount`) AS `spent`'),
    //            ]
    //        );
    //
    //        return $collection;
    //    }

    //    /**
    //     * Returns the total amount of money related to transactions without any category connected to
    //     * it. Returns either the earned amount.
    //     *
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return string
    //     */
    //    public function sumEarnedNoCategory(Collection $accounts, Carbon $start, Carbon $end): string
    //    {
    //        return $this->sumNoCategory($accounts, $start, $end, self::EARNED);
    //    }

    //    /**
    //     * Returns the total amount of money related to transactions without any category connected to
    //     * it. Returns either the spent amount.
    //     *
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return string
    //     */
    //    public function sumSpentNoCategory(Collection $accounts, Carbon $start, Carbon $end): string
    //    {
    //        $sum = $this->sumNoCategory($accounts, $start, $end, self::SPENT);
    //        if (is_null($sum)) {
    //            return '0';
    //        }
    //
    //        return $sum;
    //    }

    //    /**
    //     * Returns the total amount of money related to transactions without any category connected to
    //     * it. Returns either the earned or the spent amount.
    //     *
    //     * @param Collection $accounts
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param int        $group
    //     *
    //     * @return string
    //     */
    //    protected function sumNoCategory(Collection $accounts, Carbon $start, Carbon $end, $group = self::EARNED)
    //    {
    //        $accountIds = $accounts->pluck('id')->toArray();
    //        if ($group == self::EARNED) {
    //            $types = [TransactionType::DEPOSIT];
    //        } else {
    //            $types = [TransactionType::WITHDRAWAL];
    //        }
    //
    //        // is withdrawal or transfer AND account_from is in the list of $accounts
    //        $query = $this->user
    //            ->transactionjournals()
    //            ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
    //            ->whereNull('category_transaction_journal.id')
    //            ->before($end)
    //            ->after($start)
    //            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
    //            ->having('transaction_count', '=', 1)
    //            ->transactionTypes($types);
    //
    //        if (count($accountIds) > 0) {
    //            $query->whereIn('transactions.account_id', $accountIds);
    //        }
    //
    //
    //        $single = $query->first(
    //            [
    //                DB::raw('SUM(`transactions`.`amount`) as `sum`'),
    //                DB::raw('COUNT(`transactions`.`id`) as `transaction_count`'),
    //            ]
    //        );
    //        if (!is_null($single)) {
    //            return $single->sum;
    //        }
    //
    //        return '0';
    //
    //    }

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

    //    /**
    //     * Returns an array with the following key:value pairs:
    //     *
    //     * yyyy-mm-dd:<amount>
    //     *
    //     * Where yyyy-mm-dd is the date and <amount> is the money earned using DEPOSITS in the $category
    //     * from all the users $accounts.
    //     *
    //     * @param Category   $category
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param Collection $accounts
    //     *
    //     * @return array
    //     */
    //    public function earnedPerDay(Category $category, Carbon $start, Carbon $end, Collection $accounts): array
    //    {
    //        /** @var Collection $query */
    //        $query = $category->transactionjournals()
    //                          ->expanded()
    //                          ->transactionTypes([TransactionType::DEPOSIT])
    //                          ->before($end)
    //                          ->after($start)
    //                          ->groupBy('transaction_journals.date');
    //
    //        $query->leftJoin(
    //            'transactions as destination', function (JoinClause $join) {
    //            $join->on('destination.transaction_journal_id', '=', 'transaction_journals.id')->where('destination.amount', '>', 0);
    //        }
    //        );
    //
    //
    //        if ($accounts->count() > 0) {
    //            $ids = $accounts->pluck('id')->toArray();
    //            $query->whereIn('destination.account.id', $ids);
    //        }
    //
    //        $result = $query->get(['transaction_journals.date as dateFormatted', DB::raw('SUM(`destination`.`amount`) AS `sum`')]);
    //
    //        $return = [];
    //        foreach ($result->toArray() as $entry) {
    //            $return[$entry['dateFormatted']] = $entry['sum'];
    //        }
    //
    //        return $return;
    //    }

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
        $types    = [TransactionType::DEPOSIT, TransactionType::TRANSFER];
        $journals = $this->journalsInPeriod($categories, $accounts, $types, $start, $end);
        $sum      = '0';
        foreach ($journals as $journal) {
            $sum = bcadd(TransactionJournal::amount($journal), $sum);
        }

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

    //    /**
    //     * @param Category $category
    //     *
    //     * @return Carbon
    //     */
    //    public function getFirstActivityDate(Category $category): Carbon
    //    {
    //        /** @var TransactionJournal $first */
    //        $first = $category->transactionjournals()->orderBy('date', 'ASC')->first();
    //        if ($first) {
    //            return $first->date;
    //        }
    //
    //        return new Carbon;
    //
    //    }

    //    /**
    //     * @param Category $category
    //     * @param int      $page
    //     * @param int      $pageSize
    //     *
    //     * @return Collection
    //     */
    //    public function getJournals(Category $category, int $page, int $pageSize = 50): Collection
    //    {
    //        $offset = $page > 0 ? $page * $pageSize : 0;
    //
    //        return $category->transactionjournals()->expanded()->take($pageSize)->offset($offset)->get(TransactionJournal::queryFields());
    //
    //    }
    //
    //    /**
    //     * @param Category   $category
    //     * @param Collection $accounts
    //     *
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     *
    //     * @return Collection
    //     */
    //    public function getJournalsForAccountsInRange(Category $category, Collection $accounts, Carbon $start, Carbon $end): Collection
    //    {
    //        $ids = $accounts->pluck('id')->toArray();
    //
    //        return $category->transactionjournals()
    //                        ->after($start)
    //                        ->before($end)
    //                        ->expanded()
    //                        ->whereIn('source_account.id', $ids)
    //                        ->whereNotIn('destination_account.id', $ids)
    //                        ->get(TransactionJournal::queryFields());
    //    }

    //    /**
    //     * @param Category $category
    //     * @param Carbon   $start
    //     * @param Carbon   $end
    //     * @param int      $page
    //     * @param int      $pageSize
    //     *
    //     *
    //     * @return Collection
    //     */
    //    public function getJournalsInRange(Category $category, Carbon $start, Carbon $end, int $page, int $pageSize = 50): Collection
    //    {
    //        $offset = $page > 0 ? $page * $pageSize : 0;
    //
    //        return $category->transactionjournals()
    //                        ->after($start)
    //                        ->before($end)
    //                        ->expanded()
    //                        ->take($pageSize)
    //                        ->offset($offset)
    //                        ->get(TransactionJournal::queryFields());
    //    }

    //    /**
    //     * @param Category $category
    //     *
    //     * @return Carbon
    //     */
    //    public function getLatestActivity(Category $category): Carbon
    //    {
    //        $first  = new Carbon('1900-01-01');
    //        $second = new Carbon('1900-01-01');
    //        $latest = $category->transactionjournals()
    //                           ->orderBy('transaction_journals.date', 'DESC')
    //                           ->orderBy('transaction_journals.order', 'ASC')
    //                           ->orderBy('transaction_journals.id', 'DESC')
    //                           ->first();
    //        if ($latest) {
    //            $first = $latest->date;
    //        }
    //
    //        // could also be a transaction, nowadays:
    //        $latestTransaction = $category->transactions()
    //                                      ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
    //                                      ->orderBy('transaction_journals.date', 'DESC')
    //                                      ->orderBy('transaction_journals.order', 'ASC')
    //                                      ->orderBy('transaction_journals.id', 'DESC')
    //                                      ->first(['transactions.*', 'transaction_journals.date']);
    //        if ($latestTransaction) {
    //            $second = new Carbon($latestTransaction->date);
    //        }
    //        if ($first > $second) {
    //            return $first;
    //        }
    //
    //        return $second;
    //    }

    //    /**
    //     * Returns an array with the following key:value pairs:
    //     *
    //     * yyyy-mm-dd:<amount>
    //     *
    //     * Where yyyy-mm-dd is the date and <amount> is the money spent using DEPOSITS in the $category
    //     * from all the users accounts.
    //     *
    //     * @param Category   $category
    //     * @param Carbon     $start
    //     * @param Carbon     $end
    //     * @param Collection $accounts
    //     *
    //     * @return array
    //     */
    //    public function spentPerDay(Category $category, Carbon $start, Carbon $end, Collection $accounts): array
    //    {
    //        /** @var Collection $query */
    //        $query = $category->transactionjournals()
    //                          ->expanded()
    //                          ->transactionTypes([TransactionType::WITHDRAWAL])
    //                          ->before($end)
    //                          ->after($start)
    //                          ->groupBy('transaction_journals.date');
    //        $query->leftJoin(
    //            'transactions as source', function (JoinClause $join) {
    //            $join->on('source.transaction_journal_id', '=', 'transaction_journals.id')->where('source.amount', '<', 0);
    //        }
    //        );
    //
    //        if ($accounts->count() > 0) {
    //            $ids = $accounts->pluck('id')->toArray();
    //            $query->whereIn('source.account_id', $ids);
    //        }
    //
    //        $result = $query->get(['transaction_journals.date as dateFormatted', DB::raw('SUM(`source`.`amount`) AS `sum`')]);
    //
    //        $return = [];
    //        foreach ($result->toArray() as $entry) {
    //            $return[$entry['dateFormatted']] = $entry['sum'];
    //        }
    //
    //        return $return;
    //    }

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

        $firstJournal = $firstJournalQuery->first(['transaction_journals.*']);

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

        $firstTransaction = $firstJournalQuery->first(['transaction_journals.*']);

        if (!is_null($firstTransaction) && !is_null($first) && $firstTransaction->date < $first) {
            $first = $firstTransaction->date;
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
        $query = $this->user->transactionjournals()->expanded();
        $query->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id');
        $query->where('category_transaction_journal.category_id', $category->id);
        $first = $query->get(TransactionJournal::queryFields());

        // then collection transactions (harder)
        $query = $this->user->transactions();
        $query->leftJoin('category_transaction', 'category_transaction.transaction_id', '=', 'transactions.id');
        $query->where('category_transaction.category_id', $category->id);
        $second = $query->get(['transaction_journals.*']);


        $complete = $complete->merge($first);
        $complete = $complete->merge($second);

        // sort:
        $complete = $complete->sortByDesc(
            function (TransactionJournal $journal) {
                return $journal->date->format('Ymd');
            }
        );

        // create paginator
        $offset    = ($page - 1) * $pageSize;
        $subSet    = $complete->slice($offset, $pageSize);
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
        $query = $this->user->transactionjournals()->expanded();

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
        $query = $this->user->transactions()
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
        $second   = $query->get(['transaction_journals.*']);
        $complete = $complete->merge($first);
        $complete = $complete->merge($second);

        return $complete;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    public function journalsInPeriodWithoutCategory(Collection $accounts, Carbon $start, Carbon $end) : Collection
    {
        /** @var Collection $set */
        $query = $this->user
            ->transactionjournals()
            ->leftJoin('category_transaction_journal', 'category_transaction_journal.transaction_journal_id', '=', 'transaction_journals.id')
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
        $return    = $this->user->transactionjournals()->expanded()->whereIn('transaction_journals.id', $allIds)->get(TransactionJournal::queryFields());

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

        $lastTransaction = $lastJournalQuery->first(['transaction_journals.*']);

        if (!is_null($lastTransaction) && !is_null($last) && $lastTransaction->date < $last) {
            $last = $lastTransaction->date;
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
        $types    = [TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
        $journals = $this->journalsInPeriod($categories, $accounts, $types, $start, $end);
        $sum      = '0';
        foreach ($journals as $journal) {
            $sum = bcadd(TransactionJournal::amount($journal), $sum);
        }

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
        $journals = $this->journalsInPeriodWithoutCategory($accounts, $start, $end);
        $sum      = '0';
        foreach ($journals as $journal) {
            $sum = bcadd(TransactionJournal::amount($journal), $sum);
        }

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
}
