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

declare(strict_types=1);

namespace FireflyIII\Support\Search;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
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
    /** @var Collection */
    private $modifiers;
    /** @var  string */
    private $originalQuery = '';
    /** @var User */
    private $user;
    /** @var array */
    private $validModifiers = [];
    /** @var  array */
    private $words = [];

    /**
     * Search constructor.
     */
    public function __construct()
    {
        $this->modifiers      = new Collection;
        $this->validModifiers = (array)config('firefly.search_modifiers');
    }

    /**
     * @return string
     */
    public function getWordsAsString(): string
    {
        $string = join(' ', $this->words);
        if (strlen($string) === 0) {
            return is_string($this->originalQuery) ? $this->originalQuery : '';
        }

        return $string;
    }

    /**
     * @return bool
     */
    public function hasModifiers(): bool
    {
        return $this->modifiers->count() > 0;
    }

    /**
     * @param string $query
     */
    public function parseQuery(string $query)
    {
        $filteredQuery       = $query;
        $this->originalQuery = $query;
        $pattern             = '/[a-z_]*:[0-9a-z-.]*/i';
        $matches             = [];
        preg_match_all($pattern, $query, $matches);

        foreach ($matches[0] as $match) {
            $this->extractModifier($match);
            $filteredQuery = str_replace($match, '', $filteredQuery);
        }
        $filteredQuery = trim(str_replace(['"', "'"], '', $filteredQuery));
        if (strlen($filteredQuery) > 0) {
            $this->words = array_map('trim', explode(' ', $filteredQuery));
        }
    }

    /**
     * @return Collection
     */
    public function searchTransactions(): Collection
    {
        Log::debug('Start of searchTransactions()');
        $pageSize  = 100;
        $processed = 0;
        $page      = 1;
        $result    = new Collection();
        $startTime = microtime(true);
        do {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setLimit($pageSize)->setPage($page);
            if ($this->hasModifiers()) {
                $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
            }
            $collector->removeFilter(InternalTransferFilter::class);
            $set   = $collector->getPaginatedJournals()->getCollection();

            Log::debug(sprintf('Found %d journals to check. ', $set->count()));

            // Filter transactions that match the given triggers.
            $filtered = $set->filter(
                function (Transaction $transaction) {

                    if ($this->matchModifiers($transaction)) {
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

            // break at some point so the script does not crash:
            $currentTime = microtime(true) - $startTime;
            Log::debug(sprintf('Have been running for %f seconds.', $currentTime));

        } while (!$reachedEndOfList && !$foundEnough && $currentTime <= 30);

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
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param string $string
     */
    private function extractModifier(string $string)
    {
        $parts = explode(':', $string);
        if (count($parts) === 2 && strlen(trim(strval($parts[0]))) > 0 && strlen(trim(strval($parts[1])))) {
            $type  = trim(strval($parts[0]));
            $value = trim(strval($parts[1]));
            if (in_array($type, $this->validModifiers)) {
                // filter for valid type
                $this->modifiers->push(['type' => $type, 'value' => $value,]);
            }
        }
    }

    /**
     * @param Transaction $transaction
     *
     * @return bool
     * @throws FireflyException
     */
    private function matchModifiers(Transaction $transaction): bool
    {
        Log::debug(sprintf('Now at transaction #%d', $transaction->id));
        // first "modifier" is always the text of the search:
        // check descr of journal:
        if (count($this->words) > 0
            && !$this->strpos_arr(strtolower(strval($transaction->description)), $this->words)
            && !$this->strpos_arr(strtolower(strval($transaction->transaction_description)), $this->words)
        ) {
            Log::debug('Description does not match', $this->words);

            return false;
        }

        // then a for-each and a switch for every possible other thingie.
        foreach ($this->modifiers as $modifier) {
            $res = Modifier::apply($modifier, $transaction);
            if ($res === false) {
                return $res;
            }
        }

        return true;

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
            if (stripos($haystack, $what) !== false) {
                return true;
            }
        }

        return false;
    }
}
