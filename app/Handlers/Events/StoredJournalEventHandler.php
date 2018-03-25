<?php
/**
 * StoredJournalEventHandler.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface as JRI;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface as PRI;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface as RGRI;
use FireflyIII\Support\Events\BillScanner;
use FireflyIII\TransactionRules\Processor;

/**
 * @codeCoverageIgnore
 *
 * Class StoredJournalEventHandler
 */
class StoredJournalEventHandler
{
    /** @var JRI */
    public $journalRepository;
    /** @var PRI */
    public $repository;

    /** @var RGRI */
    public $ruleGroupRepository;

    /**
     * StoredJournalEventHandler constructor.
     *
     * @param PRI  $repository
     * @param JRI  $journalRepository
     * @param RGRI $ruleGroupRepository
     */
    public function __construct(PRI $repository, JRI $journalRepository, RGRI $ruleGroupRepository)
    {
        $this->repository          = $repository;
        $this->journalRepository   = $journalRepository;
        $this->ruleGroupRepository = $ruleGroupRepository;
    }

    /**
     * This method grabs all the users rules and processes them.
     *
     * @param StoredTransactionJournal $storedJournalEvent
     *
     * @return bool
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function processRules(StoredTransactionJournal $storedJournalEvent): bool
    {
        // get all the user's rule groups, with the rules, order by 'order'.
        $journal = $storedJournalEvent->journal;
        $groups  = $this->ruleGroupRepository->getActiveGroups($journal->user);

        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            $rules = $this->ruleGroupRepository->getActiveStoreRules($group);
            /** @var Rule $rule */
            foreach ($rules as $rule) {
                $processor = Processor::make($rule);
                $processor->handleTransactionJournal($journal);

                if ($rule->stop_processing) {
                    break;
                }
            }
        }

        return true;
    }

    /**
     * This method calls a special bill scanner that will check if the stored journal is part of a bill.
     *
     * @param StoredTransactionJournal $storedJournalEvent
     *
     * @return bool
     */
    public function scanBills(StoredTransactionJournal $storedJournalEvent): bool
    {
        $journal = $storedJournalEvent->journal;
        BillScanner::scan($journal);

        return true;
    }
}
