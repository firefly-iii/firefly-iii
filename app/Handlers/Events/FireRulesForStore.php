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

    }
}