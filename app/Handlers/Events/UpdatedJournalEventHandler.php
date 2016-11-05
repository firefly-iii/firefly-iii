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

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Rules\Processor;
use FireflyIII\Support\Events\BillScanner;

/**
 * Class UpdatedJournalEventHandler
 *
 * @package FireflyIII\Handlers\Events
 */
class UpdatedJournalEventHandler
{

    /**
     * This method will check all the rules when a journal is updated.
     *
     * @param UpdatedTransactionJournal $event
     *
     * @return bool
     */
    public function processRules(UpdatedTransactionJournal $event):bool
    {
        // get all the user's rule groups, with the rules, order by 'order'.
        $journal = $event->journal;
        $groups  = $journal->user->ruleGroups()->where('rule_groups.active', 1)->orderBy('order', 'ASC')->get();
        //
        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            $rules = $group->rules()
                           ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                           ->where('rule_triggers.trigger_type', 'user_action')
                           ->where('rule_triggers.trigger_value', 'update-journal')
                           ->where('rules.active', 1)
                           ->get(['rules.*']);
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
     * @param UpdatedTransactionJournal $event
     *
     * @return bool
     */
    public function scanBills(UpdatedTransactionJournal $event): bool
    {
        $journal = $event->journal;
        BillScanner::scan($journal);

        return true;
    }
}
