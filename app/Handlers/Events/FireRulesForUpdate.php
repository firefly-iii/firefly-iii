<?php
/**
 * FireRulesForUpdate.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;

use Auth;
use FireflyIII\Events\TransactionJournalUpdated;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Rules\Processor;
use FireflyIII\User;

/**
 * Class FireRulesForUpdate
 *
 * @package FireflyIII\Handlers\Events
 */
class FireRulesForUpdate
{
    /**
     * Handle the event.
     *
     * @param  TransactionJournalUpdated $event
     *
     * @return bool
     */
    public function handle(TransactionJournalUpdated $event): bool
    {
        // get all the user's rule groups, with the rules, order by 'order'.
        /** @var User $user */
        $user   = Auth::user();
        $groups = $user->ruleGroups()->where('rule_groups.active', 1)->orderBy('order', 'ASC')->get();
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
                $processor->handleTransactionJournal($event->journal);

                if ($rule->stop_processing) {
                    break;
                }

            }
        }

        return true;
    }
}
