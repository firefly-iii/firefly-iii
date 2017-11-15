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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Support\Events\BillScanner;
use FireflyIII\TransactionRules\Processor;

/**
 * @codeCoverageIgnore
 *
 * Class UpdatedJournalEventHandler
 *
 * @package FireflyIII\Handlers\Events
 */
class UpdatedJournalEventHandler
{
    /** @var  RuleGroupRepositoryInterface */
    public $repository;

    /**
     * StoredJournalEventHandler constructor.
     *
     * @param RuleGroupRepositoryInterface $ruleGroupRepository
     */
    public function __construct(RuleGroupRepositoryInterface $ruleGroupRepository)
    {
        $this->repository = $ruleGroupRepository;
    }

    /**
     * This method will check all the rules when a journal is updated.
     *
     * @param UpdatedTransactionJournal $updatedJournalEvent
     *
     * @return bool
     */
    public function processRules(UpdatedTransactionJournal $updatedJournalEvent): bool
    {
        // get all the user's rule groups, with the rules, order by 'order'.
        $journal = $updatedJournalEvent->journal;
        $groups  = $this->repository->getActiveGroups($journal->user);

        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            $rules = $this->repository->getActiveUpdateRules($group);
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
     * This method calls a special bill scanner that will check if the updated journal is part of a bill.
     *
     * @param UpdatedTransactionJournal $updatedJournalEvent
     *
     * @return bool
     */
    public function scanBills(UpdatedTransactionJournal $updatedJournalEvent): bool
    {
        $journal = $updatedJournalEvent->journal;
        BillScanner::scan($journal);

        return true;
    }
}
