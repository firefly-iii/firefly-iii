<?php
/**
 * UpdatedJournalEventHandler.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use FireflyIII\Support\Events\BillScanner;

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
