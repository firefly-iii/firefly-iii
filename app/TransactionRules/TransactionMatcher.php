<?php
/**
 * TransactionMatcher.php
 * Copyright (C) 2016 Robert Horlings.
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules;

use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Rule;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionMatcher is used to find a list of
 * transaction matching a set of triggers.
 */
class TransactionMatcher
{
    /** @var int Limit of matcher */
    private $limit = 10;
    /** @var int Maximum number of transaction to search in (for performance reasons) * */
    private $range = 200;
    /** @var Rule The rule to apply */
    private $rule;
    /** @var JournalTaskerInterface Tasker for some related tasks */
    private $tasker;
    /** @var array Types that can be matched using this matcher */
    private $transactionTypes = [TransactionType::DEPOSIT, TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
    /** @var array List of triggers to match */
    private $triggers = [];

    /**
     * TransactionMatcher constructor. Typehint the repository.
     *
     * @param JournalTaskerInterface $tasker
     */
    public function __construct(JournalTaskerInterface $tasker)
    {
        $this->tasker = $tasker;
    }

    /**
     * This method will search the user's transaction journal (with an upper limit of $range) for
     * transaction journals matching the given rule. This is accomplished by trying to fire these
     * triggers onto each transaction journal until enough matches are found ($limit).
     *
     * @return Collection
     */
    public function findTransactionsByRule()
    {
        if (0 === count($this->rule->ruleTriggers)) {
            return new Collection;
        }

        // Variables used within the loop
        $processor = Processor::make($this->rule, false);
        $result    = $this->runProcessor($processor);

        // If the list of matchingTransactions is larger than the maximum number of results
        // (e.g. if a large percentage of the transactions match), truncate the list
        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     * This method will search the user's transaction journal (with an upper limit of $range) for
     * transaction journals matching the given $triggers. This is accomplished by trying to fire these
     * triggers onto each transaction journal until enough matches are found ($limit).
     *
     * @return Collection
     */
    public function findTransactionsByTriggers(): Collection
    {
        if (0 === count($this->triggers)) {
            return new Collection;
        }

        // Variables used within the loop
        $processor = Processor::makeFromStringArray($this->triggers);
        $result    = $this->runProcessor($processor);

        // If the list of matchingTransactions is larger than the maximum number of results
        // (e.g. if a large percentage of the transactions match), truncate the list
        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     * Return limit
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Set limit
     *
     * @param int $limit
     *
     * @return TransactionMatcher
     */
    public function setLimit(int $limit): TransactionMatcher
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get range
     * @return int
     */
    public function getRange(): int
    {
        return $this->range;
    }

    /**
     * Set range
     *
     * @param int $range
     *
     * @return TransactionMatcher
     */
    public function setRange(int $range): TransactionMatcher
    {
        $this->range = $range;

        return $this;
    }

    /**
     * Get triggers
     * @return array
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

    /**
     * Set triggers
     *
     * @param array $triggers
     *
     * @return TransactionMatcher
     */
    public function setTriggers(array $triggers): TransactionMatcher
    {
        $this->triggers = $triggers;

        return $this;
    }

    /**
     * Set rule
     *
     * @param Rule $rule
     */
    public function setRule(Rule $rule)
    {
        $this->rule = $rule;
    }

    /**
     * Run the processor.
     *
     * @param Processor $processor
     *
     * @return Collection
     */
    private function runProcessor(Processor $processor): Collection
    {
        // Start a loop to fetch batches of transactions. The loop will finish if:
        //   - all transactions have been fetched from the database
        //   - the maximum number of transactions to return has been found
        //   - the maximum number of transactions to search in have been searched
        $pageSize  = min($this->range / 2, $this->limit * 2);
        $processed = 0;
        $page      = 1;
        $result    = new Collection();
        do {
            // Fetch a batch of transactions from the database
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setUser(auth()->user());
            $collector->setAllAssetAccounts()->setLimit($pageSize)->setPage($page)->setTypes($this->transactionTypes);
            $set = $collector->getPaginatedJournals();
            Log::debug(sprintf('Found %d journals to check. ', $set->count()));

            // Filter transactions that match the given triggers.
            $filtered = $set->filter(
                function (Transaction $transaction) use ($processor) {
                    Log::debug(sprintf('Test these triggers on journal #%d (transaction #%d)', $transaction->transaction_journal_id, $transaction->id));

                    return $processor->handleTransaction($transaction);
                }
            );

            Log::debug(sprintf('Found %d journals that match.', $filtered->count()));

            // merge:
            /** @var Collection $result */
            $result = $result->merge($filtered);
            Log::debug(sprintf('Total count is now %d', $result->count()));

            // Update counters
            ++$page;
            $processed += count($set);

            Log::debug(sprintf('Page is now %d, processed is %d', $page, $processed));

            // Check for conditions to finish the loop
            $reachedEndOfList = $set->count() < 1;
            $foundEnough      = $result->count() >= $this->limit;
            $searchedEnough   = ($processed >= $this->range);

            Log::debug(sprintf('reachedEndOfList: %s', var_export($reachedEndOfList, true)));
            Log::debug(sprintf('foundEnough: %s', var_export($foundEnough, true)));
            Log::debug(sprintf('searchedEnough: %s', var_export($searchedEnough, true)));
        } while (!$reachedEndOfList && !$foundEnough && !$searchedEnough);

        return $result;
    }
}
