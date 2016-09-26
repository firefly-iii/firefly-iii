<?php
/**
 * Search.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Search;


use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;

/**
 * Class Search
 *
 * @package FireflyIII\Search
 */
class Search implements SearchInterface
{
    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchAccounts(array $words): Collection
    {
        return auth()->user()->accounts()->with('accounttype')->where(
            function (EloquentBuilder $q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('name', 'LIKE', '%' . e($word) . '%');
                }
            }
        )->get();
    }

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchBudgets(array $words): Collection
    {
        /** @var Collection $set */
        $set    = auth()->user()->budgets()->get();
        $newSet = $set->filter(
            function (Budget $b) use ($words) {
                $found = 0;
                foreach ($words as $word) {
                    if (!(strpos(strtolower($b->name), strtolower($word)) === false)) {
                        $found++;
                    }
                }

                return $found > 0;
            }
        );

        return $newSet;
    }

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchCategories(array $words): Collection
    {
        /** @var Collection $set */
        $set    = auth()->user()->categories()->get();
        $newSet = $set->filter(
            function (Category $c) use ($words) {
                $found = 0;
                foreach ($words as $word) {
                    if (!(strpos(strtolower($c->name), strtolower($word)) === false)) {
                        $found++;
                    }
                }

                return $found > 0;
            }
        );

        return $newSet;
    }

    /**
     *
     * @param array $words
     *
     * @return Collection
     */
    public function searchTags(array $words): Collection
    {
        return new Collection;
    }

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchTransactions(array $words): Collection
    {
        // decrypted transaction journals:
        $decrypted = auth()->user()->transactionJournals()->expanded()->where('transaction_journals.encrypted', 0)->where(
            function (EloquentBuilder $q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('transaction_journals.description', 'LIKE', '%' . e($word) . '%');
                }
            }
        )->get(TransactionJournal::queryFields());

        // encrypted
        $all      = auth()->user()->transactionJournals()->expanded()->where('transaction_journals.encrypted', 1)->get(TransactionJournal::queryFields());
        $set      = $all->filter(
            function (TransactionJournal $journal) use ($words) {
                foreach ($words as $word) {
                    $haystack = strtolower($journal->description);
                    $word     = strtolower($word);
                    if (!(strpos($haystack, $word) === false)) {
                        return $journal;
                    }
                }

                return null;

            }
        );
        $filtered = $set->merge($decrypted);
        $filtered = $filtered->sortBy(
            function (TransactionJournal $journal) {
                return intval($journal->date->format('U'));
            }
        );

        $filtered = $filtered->reverse();

        return $filtered;
    }
} 
