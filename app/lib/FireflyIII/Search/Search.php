<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 13/11/14
 * Time: 21:01
 */

namespace FireflyIII\Search;


use Illuminate\Support\Collection;

class Search
{
    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchAccounts(array $words)
    {
        return \Auth::user()->accounts()->with('accounttype')->where(
            function ($q) use ($words) {
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
    public function searchBudgets(array $words)
    {
        /** @var Collection $set */
        $set    = \Auth::user()->budgets()->get();
        $newSet = $set->filter(
            function (\Budget $b) use ($words) {
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
    public function searchCategories(array $words)
    {
        /** @var Collection $set */
        $set    = \Auth::user()->categories()->get();
        $newSet = $set->filter(
            function (\Category $c) use ($words) {
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
     * @param array $words
     *
     * @return Collection
     */
    public function searchTags(array $words)
    {
        return new Collection;
    }

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchTransactions(array $words)
    {
        return \Auth::user()->transactionjournals()->withRelevantData()->where(
            function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('description', 'LIKE', '%' . e($word) . '%');
                }
            }
        )->get();
    }
} 