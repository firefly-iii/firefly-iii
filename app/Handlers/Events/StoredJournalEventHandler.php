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
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Processor;

/**
 * Class StoredJournalEventHandler
 */
class StoredJournalEventHandler
{
    /** @var JournalRepositoryInterface The journal repository. */
    public $journalRepository;
    /** @var PiggyBankRepositoryInterface The Piggy bank repository */
    public $repository;
    /** @var RuleGroupRepositoryInterface The rule group repository */
    public $ruleGroupRepository;

    /**
     * StoredJournalEventHandler constructor.
     *
     * @param PiggyBankRepositoryInterface $repository
     * @param JournalRepositoryInterface   $journalRepository
     * @param RuleGroupRepositoryInterface $ruleGroupRepository
     */
    public function __construct(
        PiggyBankRepositoryInterface $repository, JournalRepositoryInterface $journalRepository, RuleGroupRepositoryInterface $ruleGroupRepository
    ) {
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

}
