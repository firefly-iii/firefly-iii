<?php
/**
 * Search.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Search;


use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class Search
 *
 * @package FireflyIII\Search
 */
class Search implements SearchInterface
{
    /** @var int */
    private $limit = 100;
    /** @var User */
    private $user;

    /**
     * AttachmentRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * The search will assume that the user does not have so many accounts
     * that this search should be paginated.
     *
     * @param array $words
     *
     * @return Collection
     */
    public function searchAccounts(array $words): Collection
    {
        $accounts = $this->user->accounts()->get();
        /** @var Collection $result */
        $result = $accounts->filter(
            function (Account $account) use ($words) {
                if ($this->strpos_arr(strtolower($account->name), $words)) {
                    return $account;
                }

                return false;
            }
        );

        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchBudgets(array $words): Collection
    {
        /** @var Collection $set */
        $set = auth()->user()->budgets()->get();
        /** @var Collection $result */
        $result = $set->filter(
            function (Budget $budget) use ($words) {
                if ($this->strpos_arr(strtolower($budget->name), $words)) {
                    return $budget;
                }

                return false;
            }
        );

        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     * Search assumes the user does not have that many categories. So no paginated search.
     *
     * @param array $words
     *
     * @return Collection
     */
    public function searchCategories(array $words): Collection
    {
        $categories = $this->user->categories()->get();
        /** @var Collection $result */
        $result = $categories->filter(
            function (Category $category) use ($words) {
                if ($this->strpos_arr(strtolower($category->name), $words)) {
                    return $category;
                }

                return false;
            }
        );
        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     *
     * @param array $words
     *
     * @return Collection
     */
    public function searchTags(array $words): Collection
    {
        $tags = $this->user->tags()->get();

        /** @var Collection $result */
        $result = $tags->filter(
            function (Tag $tag) use ($words) {
                if ($this->strpos_arr(strtolower($tag->tag), $words)) {
                    return $tag;
                }

                return false;
            }
        );
        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     * @param array $words
     *
     * @return Collection
     */
    public function searchTransactions(array $words): Collection
    {
        $pageSize  = 100;
        $processed = 0;
        $page      = 1;
        $result    = new Collection();
        do {
            $collector = new JournalCollector($this->user);
            $collector->setAllAssetAccounts()->setLimit($pageSize)->setPage($page);
            $set = $collector->getPaginatedJournals();
            Log::debug(sprintf('Found %d journals to check. ', $set->count()));

            // Filter transactions that match the given triggers.
            $filtered = $set->filter(
                function (Transaction $transaction) use ($words) {
                    // check descr of journal:
                    if ($this->strpos_arr(strtolower(strval($transaction->description)), $words)) {
                        return $transaction;
                    }

                    // check descr of transaction
                    if ($this->strpos_arr(strtolower(strval($transaction->transaction_description)), $words)) {
                        return $transaction;
                    }

                    // return false:
                    return false;
                }
            );

            Log::debug(sprintf('Found %d journals that match.', $filtered->count()));

            // merge:
            /** @var Collection $result */
            $result = $result->merge($filtered);
            Log::debug(sprintf('Total count is now %d', $result->count()));

            // Update counters
            $page++;
            $processed += count($set);

            Log::debug(sprintf('Page is now %d, processed is %d', $page, $processed));

            // Check for conditions to finish the loop
            $reachedEndOfList = $set->count() < 1;
            $foundEnough      = $result->count() >= $this->limit;

            Log::debug(sprintf('reachedEndOfList: %s', var_export($reachedEndOfList, true)));
            Log::debug(sprintf('foundEnough: %s', var_export($foundEnough, true)));

        } while (!$reachedEndOfList && !$foundEnough);

        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * @param string $haystack
     * @param array  $needle
     *
     * @return bool
     */
    private function strpos_arr(string $haystack, array $needle)
    {
        if (strlen($haystack) === 0) {
            return false;
        }
        foreach ($needle as $what) {
            if (($pos = strpos($haystack, $what)) !== false) {
                return true;
            }
        }

        return false;
    }
} 
