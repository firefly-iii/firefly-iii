<?php
declare(strict_types = 1);
/**
 * TransactionMatcher.php
 * Copyright (C) 2016 Robert Horlings
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class TransactionMatcher is used to find a list of
 * transaction matching a set of triggers
 *
 * @package FireflyIII\Rules
 */
class TransactionMatcher
{
    /** @var int */
    private $limit = 10;
    /** @var int Maximum number of transaction to search in (for performance reasons) * */
    private $range = 200;
    /** @var  JournalRepositoryInterface */
    private $repository;
    /** @var array */
    private $transactionTypes = [TransactionType::DEPOSIT, TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
    /** @var array List of triggers to match */
    private $triggers = [];

    /**
     * TransactionMatcher constructor. Typehint the repository.
     *
     * @param JournalRepositoryInterface $repository
     */
    public function __construct(JournalRepositoryInterface $repository)
    {
        $this->repository = $repository;

    }

    /**
     * This method will search the user's transaction journal (with an upper limit of $range) for
     * transaction journals matching the given $triggers. This is accomplished by trying to fire these
     * triggers onto each transaction journal until enough matches are found ($limit).
     *
     * @return Collection
     *
     */
    public function findMatchingTransactions(): Collection
    {
        if (count($this->triggers) === 0) {
            return new Collection;
        }
        $pagesize = min($this->range / 2, $this->limit * 2);

        // Variables used within the loop
        $processed = 0;
        $page      = 1;
        $result    = new Collection();
        $processor = Processor::makeFromStringArray($this->triggers);

        // Start a loop to fetch batches of transactions. The loop will finish if:
        //   - all transactions have been fetched from the database
        //   - the maximum number of transactions to return has been found
        //   - the maximum number of transactions to search in have been searched 
        do {
            // Fetch a batch of transactions from the database
            $paginator = $this->repository->getJournals($this->transactionTypes, $page, $pagesize);
            $set       = $paginator->getCollection();


            // Filter transactions that match the given triggers.
            $filtered = $set->filter(
                function (TransactionJournal $journal) use ($processor) {
                    return $processor->handleTransactionJournal($journal);
                }
            );

            // merge:
            $result = $result->merge($filtered);

            // Update counters
            $page++;
            $processed += count($set);

            // Check for conditions to finish the loop
            $reachedEndOfList = $set->count() < $pagesize;
            $foundEnough      = $result->count() >= $this->limit;
            $searchedEnough   = ($processed >= $this->range);
        } while (!$reachedEndOfList && !$foundEnough && !$searchedEnough);

        // If the list of matchingTransactions is larger than the maximum number of results
        // (e.g. if a large percentage of the transactions match), truncate the list
        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
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
     * @return int
     */
    public function getRange(): int
    {
        return $this->range;
    }

    /**
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
     * @return array
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

    /**
     * @param array $triggers
     *
     * @return TransactionMatcher
     */
    public function setTriggers(array $triggers): TransactionMatcher
    {
        $this->triggers = $triggers;

        return $this;
    }


}
