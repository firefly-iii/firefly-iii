<?php
/**
 * FireRulesForUpdate.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\TransactionJournalUpdated;
use Log;

/**
 * Class FireRulesForUpdate
 *
 * @package FireflyIII\Handlers\Events
 */
class FireRulesForUpdate
{
    /**
     * Create the event handler.
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  TransactionJournalUpdated $event
     *
     * @return void
     */
    public function handle(TransactionJournalUpdated $event)
    {
        Log::debug('Fire rules for update!');

    }
}