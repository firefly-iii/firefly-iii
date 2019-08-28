<?php
/**
 * TransactionMatcher.php
 * Copyright (C) 2017 Robert Horlings.
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\TransactionRules;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionMatcher is used to find a list of
 * transaction matching a set of triggers.
 */
class TransactionMatcher
{
    /** @var Collection Asset accounts to search in. */
    private $accounts;
    /** @var Carbon Start date of the matched transactions */
    private $endDate;
    /** @var string */
    private $exactAmount;
    /** @var string */
    private $maxAmount;
    /** @var string */
    private $minAmount;
    /** @var Rule The rule to apply */
    private $rule;
    /** @var int Maximum number of transaction to search in (for performance reasons) */
    private $searchLimit;
    /** @var Carbon Start date of the matched transactions */
    private $startDate;
    /** @var bool */
    private $strict;
    /** @var array Types that can be matched using this matcher */
    private $transactionTypes = [TransactionType::DEPOSIT, TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
    /** @var int Max number of results */
    private $triggeredLimit;
    /** @var array List of triggers to match */
    private $triggers = [];

    /**
     * TransactionMatcher constructor.
     */
    public function __construct()
    {
        $this->strict   = false;
        $this->accounts = new Collection;
        Log::debug('Created new transaction matcher');
    }

    /**
     * This method will search the user's transaction journal (with an upper limit of $range) for
     * transaction journals matching the given rule. This is accomplished by trying to fire these
     * triggers onto each transaction journal until enough matches are found ($limit).
     *
     * @return array
     * @throws FireflyException
     */
    public function findTransactionsByRule(): array
    {
        Log::debug('Now in findTransactionsByRule()');
        if (0 === count($this->rule->ruleTriggers)) {
            Log::error('Rule has no triggers!');

            return [];
        }

        // Variables used within the loop.
        /** @var Processor $processor */
        $processor = app(Processor::class);
        $processor->make($this->rule, false);
        $result = $this->runProcessor($processor);

        // If the list of matchingTransactions is larger than the maximum number of results
        // (e.g. if a large percentage of the transactions match), truncate the list
        $result = array_slice($result, 0, $this->searchLimit);

        return $result;
    }

    /**
     * This method will search the user's transaction journal (with an upper limit of $range) for
     * transaction journals matching the given $triggers. This is accomplished by trying to fire these
     * triggers onto each transaction journal until enough matches are found ($limit).
     *
     * @return array
     * @throws FireflyException
     */
    public function findTransactionsByTriggers(): array
    {
        if (0 === count($this->triggers)) {
            return new Collection;
        }

        // Variables used within the loop
        /** @var Processor $processor */
        $processor = app(Processor::class);
        $processor->makeFromStringArray($this->triggers);
        $processor->setStrict($this->strict);
        $result = $this->runProcessor($processor);

        // If the list of matchingTransactions is larger than the maximum number of results
        // (e.g. if a large percentage of the transactions match), truncate the list
        return array_slice($result, 0, $this->searchLimit);
    }

    /**
     * Get triggers
     *
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
     * @return bool
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * @param bool $strict
     */
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    /**
     * @param Collection $accounts
     */
    public function setAccounts(Collection $accounts): void
    {
        $this->accounts = $accounts;
    }

    /**
     * @param Carbon|null $endDate
     */
    public function setEndDate(Carbon $endDate = null): void
    {
        $this->endDate = $endDate;
    }

    /**
     * Set rule
     *
     * @param Rule $rule
     */
    public function setRule(Rule $rule): void
    {
        $this->rule = $rule;
    }

    /**
     * @param int $searchLimit
     */
    public function setSearchLimit(int $searchLimit): void
    {
        $this->searchLimit = $searchLimit;
    }

    /**
     * @param Carbon|null $startDate
     */
    public function setStartDate(Carbon $startDate = null): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @param int $triggeredLimit
     */
    public function setTriggeredLimit(int $triggeredLimit): void
    {
        $this->triggeredLimit = $triggeredLimit;
    }

    /**
     *
     */
    private function readTriggers(): void
    {
        $valid = ['amount_less', 'amount_more', 'amount_exactly'];
        if (null !== $this->rule) {
            $allTriggers = $this->rule->ruleTriggers()->whereIn('trigger_type', $valid)->get();
            /** @var RuleTrigger $trigger */
            foreach ($allTriggers as $trigger) {
                if ('amount_less' === $trigger->trigger_type) {
                    $this->maxAmount = $trigger->trigger_value;
                    Log::debug(sprintf('Set max amount to be %s', $trigger->trigger_value));
                }
                if ('amount_more' === $trigger->trigger_type) {
                    $this->minAmount = $trigger->trigger_value;
                    Log::debug(sprintf('Set min amount to be %s', $trigger->trigger_value));
                }
                if ('amount_exactly' === $trigger->trigger_type) {
                    $this->exactAmount = $trigger->trigger_value;
                    Log::debug(sprintf('Set exact amount to be %s', $trigger->trigger_value));
                }
            }
        }
    }

    /**
     * Run the processor.
     *
     * @param Processor $processor
     *
     * @return array
     */
    private function runProcessor(Processor $processor): array
    {
        Log::debug('Now in runprocessor()');
        // since we have a rule in $this->rule, we can add some of the triggers
        // to the Journal Collector.
        // Firefly III will then have to search through less transactions.
        $this->readTriggers();

        // Start a loop to fetch batches of transactions. The loop will finish if:
        //   - all transactions have been fetched from the database
        //   - the maximum number of transactions to return has been found
        //   - the maximum number of transactions to search in have been searched
        $pageSize    = $this->searchLimit;
        $processed   = 0;
        $page        = 1;
        $totalResult = [];

        Log::debug(sprintf('Search limit is %d, triggered limit is %d, so page size is %d', $this->searchLimit, $this->triggeredLimit, $pageSize));

        do {
            Log::debug('Start of do-loop');
            // Fetch a batch of transactions from the database

            /** @var GroupCollectorInterface $collector */
            $collector = app(GroupCollectorInterface::class);

            /** @var User $user */
            $user = auth()->user();

            $collector->setUser($user);

            // limit asset accounts:
            if ($this->accounts->count() > 0) {
                $collector->setAccounts($this->accounts);
            }
            if (null !== $this->startDate && null !== $this->endDate) {
                $collector->setRange($this->startDate, $this->endDate);
            }

            $collector->setLimit($pageSize)->setPage($page)->setTypes($this->transactionTypes);
            if (null !== $this->maxAmount) {
                Log::debug(sprintf('Amount must be less than %s', $this->maxAmount));
                $collector->amountLess($this->maxAmount);
            }
            if (null !== $this->minAmount) {
                Log::debug(sprintf('Amount must be more than %s', $this->minAmount));
                $collector->amountMore($this->minAmount);
            }
            if (null !== $this->exactAmount) {
                Log::debug(sprintf('Amount must be exactly %s', $this->exactAmount));
                $collector->amountIs($this->exactAmount);
            }

            $journals = $collector->getExtractedJournals();
            Log::debug(sprintf('Found %d transaction journals to check. ', count($journals)));

            // Filter transactions that match the given triggers.
            $filtered = [];
            /** @var array $journal */
            foreach ($journals as $journal) {
                $result = $processor->handleJournalArray($journal);
                if ($result) {
                    $filtered[] = $journal;
                }
            }

            Log::debug(sprintf('Found %d journals that match.', count($filtered)));

            // merge:
            $totalResult = $totalResult + $filtered;
            Log::debug(sprintf('Total count is now %d', count($totalResult)));

            // Update counters
            ++$page;
            $processed += count($journals);

            Log::debug(sprintf('Page is now %d, processed is %d', $page, $processed));

            // Check for conditions to finish the loop
            $reachedEndOfList = count($journals) < 1;
            $foundEnough      = count($totalResult) >= $this->triggeredLimit;
            $searchedEnough   = ($processed >= $this->searchLimit);

            Log::debug(sprintf('reachedEndOfList: %s', var_export($reachedEndOfList, true)));
            Log::debug(sprintf('foundEnough: %s', var_export($foundEnough, true)));
            Log::debug(sprintf('searchedEnough: %s', var_export($searchedEnough, true)));
        } while (!$reachedEndOfList && !$foundEnough && !$searchedEnough);
        Log::debug('End of do-loop');

        return $totalResult;
    }
}
