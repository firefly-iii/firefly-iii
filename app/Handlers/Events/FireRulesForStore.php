<?php
declare(strict_types = 1);
/**
 * FireRulesForStore.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\TransactionJournalStored;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Rules\Processor;
use FireflyIII\User;
use Illuminate\Support\Facades\Auth;

/**
 * Class FireRulesForStore
 *
 * @package FireflyIII\Handlers\Events
 */
class FireRulesForStore
{

    /**
     * Connect a new transaction journal to any related piggy banks.
     *
     * @param  TransactionJournalStored $event
     *
     * @return bool
     */
    public function handle(TransactionJournalStored $event): bool
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
                           ->where('rule_triggers.trigger_value', 'store-journal')
                           ->where('rules.active', 1)
                           ->get(['rules.*']);
            /** @var Rule $rule */
            foreach ($rules as $rule) {

                $processor = Processor::make($rule);
                $processor->handleTransactionJournal($event->journal);

                if ($rule->stop_processing) {
                    return true;
                }

            }
        }

        return true;
    }
}
