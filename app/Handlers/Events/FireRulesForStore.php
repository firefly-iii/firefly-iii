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
use FireflyIII\Models\RuleGroup;
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
        // get all the user's rule groups, with the rules, order by 'order'.
        /** @var User $user */
        $user = Auth::user();

        $groups = $user->ruleGroups()->with('rules')->orderBy('order','ASC')->get();

        /** @var RuleGroup $group */
        foreach($groups as $group) {

        }
    }
}