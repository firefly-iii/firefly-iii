<?php
/**
 * FireRulesForStore.php
 * Copyright (C) 2016 Sander Dorigo
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
use Log;

/**
 * Class FireRulesForStore
 *
 * @package FireflyIII\Handlers\Events
 */
class FireRulesForStore
{
    /**
     * Create the event handler.
     *
     * @codeCoverageIgnore
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Connect a new transaction journal to any related piggy banks.
     *
     * @param  TransactionJournalStored $event
     *
     * @return boolean
     */
    public function handle(TransactionJournalStored $event)
    {
        Log::debug('Before event (in handle). From account name is: ' . $event->journal->source_account->name);
        // get all the user's rule groups, with the rules, order by 'order'.
        /** @var User $user */
        $user   = Auth::user();
        $groups = $user->ruleGroups()->orderBy('order', 'ASC')->get();
        //
        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            Log::debug('Now processing group "' . $group->title . '".');
            $rules = $group->rules()
                           ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                           ->where('rule_triggers.trigger_type', 'user_action')
                           ->where('rule_triggers.trigger_value', 'store-journal')
                           ->get(['rules.*']);
            /** @var Rule $rule */
            foreach ($rules as $rule) {
                Log::debug('Now handling rule #' . $rule->id . ' (' . $rule->title. ')');
                $processor = new Processor($rule, $event->journal);

                // get some return out of this?
                $processor->handle();

                if($rule->stop_processing) {
                    break;
                }

            }
        }
//        echo 'Done processing rules. See log.';
//        exit;
    }
}