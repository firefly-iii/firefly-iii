<?php
/**
 * StoredGroupEventHandler.php
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

use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Processor;

/**
 * Class StoredGroupEventHandler
 */
class StoredGroupEventHandler
{
    /**
     * This method grabs all the users rules and processes them.
     *
     * @param StoredTransactionGroup $storedJournalEvent
     *
     * @return bool
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function processRules(StoredTransactionGroup $storedJournalEvent): bool
    {
        $journals = $storedJournalEvent->transactionGroup->transactionJournals;
        if(false === $storedJournalEvent->applyRules) {
            return true;
        }
        die('cannot apply rules yet');
        // create objects:
        /** @var RuleGroupRepositoryInterface $ruleGroupRepos */
        $ruleGroupRepos = app(RuleGroupRepositoryInterface::class);

        foreach ($journals as $journal) {
            $ruleGroupRepos->setUser($journal->user);
            $groups = $ruleGroupRepos->getActiveGroups();

            /** @var RuleGroup $group */
            foreach ($groups as $group) {
                $rules = $ruleGroupRepos->getActiveStoreRules($group);
                /** @var Rule $rule */
                foreach ($rules as $rule) {
                    /** @var Processor $processor */
                    $processor = app(Processor::class);
                    $processor->make($rule);
                    $processor->handleTransactionJournal($journal);

                    // TODO refactor the stop_processing logic.
                    // TODO verify that rule execution happens in one place only, including the journal + rule loop (if any)
                    if ($rule->stop_processing) {
                        break;
                    }
                }
            }
        }

        return true;
    }

}
