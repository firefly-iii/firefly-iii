<?php
/**
 * UpdatedJournalEventHandler.php
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

use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Processor;

/**
 * Class UpdatedJournalEventHandler
 */
class UpdatedJournalEventHandler
{
    /**
     * This method will check all the rules when a journal is updated.
     *
     * @param UpdatedTransactionJournal $updatedJournalEvent
     *
     * @return bool
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function processRules(UpdatedTransactionJournal $updatedJournalEvent): bool
    {
        // get all the user's rule groups, with the rules, order by 'order'.
        $journal = $updatedJournalEvent->journal;

        /** @var RuleGroupRepositoryInterface $ruleGroupRepos */
        $ruleGroupRepos = app(RuleGroupRepositoryInterface::class);
        $ruleGroupRepos->setUser($journal->user);

        $groups  = $ruleGroupRepos->getActiveGroups($journal->user);

        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            $rules = $ruleGroupRepos->getActiveUpdateRules($group);
            /** @var Rule $rule */
            foreach ($rules as $rule) {
                /** @var Processor $processor */
                $processor = app(Processor::class);
                $processor->make($rule);
                $processor->handleTransactionJournal($journal);

                if ($rule->stop_processing) {
                    break;
                }
            }
        }

        return true;
    }

}
