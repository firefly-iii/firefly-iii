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

use FireflyIII\Models\Rule;
use FireflyIII\Models\TransactionType;

/**
 * Class TransactionMatcher is used to find a list of 
 * transaction matching a set of triggers
 *
 * @package FireflyIII\Rules
 */
class TransactionMatcher
{
    /** @var array List of triggers to match*/
    protected $triggers = []; 
    
    /** @var int Maximum number of transaction to search in (for performance reasons) **/
    protected $maxTransactionsToSearchIn = 1000;
    
    /** @var array */
    protected $transactionTypes = [ TransactionType::DEPOSIT, TransactionType::WITHDRAWAL, TransactionType::TRANSFER ];

    /**
     * Default constructor
     *
     * @param Rule               $rule
     * @param TransactionJournal $journal
     */
    public function __construct($triggers)
    {
        $this->setTriggers($triggers);
    }

    /**
     * Find matching transactions for the current set of triggers
     * @param number $maxResults The maximum number of transactions returned
     */
    public function findMatchingTransactions($maxResults = 50) {
        /** @var JournalRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Journal\JournalRepositoryInterface');
        
        // We don't know the number of transaction to fetch from the database, in
        // order to return the proper number of matching transactions. Since we don't want
        // to fetch all transactions (as the first transactions already match, or the last
        // transactions are irrelevant), we will fetch data in pages.
        
        // The optimal pagesize is somewhere between the maximum number of results to be returned
        // and the maximum number of transactions to consider.
        $pagesize = min($this->maxTransactionsToSearchIn / 2, $maxResults * 2);
        
        // Variables used within the loop
        $numTransactionsProcessed = 0;
        $page = 1;
        $matchingTransactions = [];
        
        // Flags to indicate the end of the loop
        $reachedEndOfList = false;
        $foundEnoughTransactions = false;
        $searchedEnoughTransactions = false;
                
        // Start a loop to fetch batches of transactions. The loop will finish if:
        //   - all transactions have been fetched from the database
        //   - the maximum number of transactions to return has been found
        //   - the maximum number of transactions to search in have been searched 
        do {
            // Fetch a batch of transactions from the database
            $offset = $page > 0 ? ($page - 1) * $pagesize : 0;
            $transactions = $repository->getJournalsOfTypes( $this->transactionTypes, $offset, $page, $pagesize)->getCollection()->all();
        
            // Filter transactions that match the rule
            $matchingTransactions += array_filter( $transactions, function($transaction) {
                $processor = new Processor(new Rule, $transaction);
                return $processor->isTriggeredBy($this->triggers);
            });
        
            // Update counters
            $page++;
            $numTransactionsProcessed += count($transactions);
            
            // Check for conditions to finish the loop
            $reachedEndOfList           = (count($transactions) < $pagesize);
            $foundEnoughTransactions    = (count($matchingTransactions) >= $maxResults);
            $searchedEnoughTransactions    = ($numTransactionsProcessed >= $this->maxTransactionsToSearchIn);
        } while( !$reachedEndOfList && !$foundEnoughTransactions && !$searchedEnoughTransactions);
        
        // If the list of matchingTransactions is larger than the maximum number of results
        // (e.g. if a large percentage of the transactions match), truncate the list
        $matchingTransactions = array_slice($matchingTransactions, 0, $maxResults);
        
        return $matchingTransactions;
    }
    
    /**
     * @return array
     */
    public function getTriggers() {
        return $this->triggers;
    }
    
    /**
     * @param array $triggers
     */
    public function setTriggers($triggers) {
        $this->triggers = $triggers;
        return $this;
    }

    /**
     * @return array
     */
    public function getTransactionLimit() {
        return $this->maxTransactionsToSearchIn;
    }
    
    /**
     * @param int $limit
     */
    public function setTransactionLimit(int $limit) {
        $this->maxTransactionsToSearchIn = $limit;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getTransactionTypes() {
        return $this->transactionTypes;
    }

    /**
     * @param array $transactionTypes
     */
    public function setTransactionTypes(array $transactionTypes) {
        $this->transactionTypes = $transactionTypes;
        return $this;
    }
    
}
