<?php
declare(strict_types = 1);
/**
 * UserAction.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Triggers;

use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionJournal;
use Log;

/**
 * Class UserAction
 *
 * @package FireflyIII\Rules\Triggers
 */
class UserAction implements TriggerInterface
{
    /** @var RuleTrigger */
    protected $trigger;

    /** @var TransactionJournal */
    protected $journal;


    /**
     * TriggerInterface constructor.
     *
     * @param RuleTrigger        $trigger
     * @param TransactionJournal $journal
     */
    public function __construct(RuleTrigger $trigger, TransactionJournal $journal)
    {
        $this->trigger = $trigger;
        $this->journal = $journal;

    }

    /**
     * This trigger is always triggered, because the rule that it is a part of has been pre-selected on this condition.
     *
     * @return bool
     */
    public function triggered()
    {
        Log::debug('user_action always returns true.');

        return true;
    }

}
