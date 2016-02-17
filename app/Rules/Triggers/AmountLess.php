<?php
declare(strict_types = 1);
/**
 * AmountLess.php
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
 * Class AmountLess
 *
 * @package FireflyIII\Rules\Triggers
 */
class AmountLess implements TriggerInterface
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
     * @return bool
     */
    public function triggered()
    {
        $amount  = $this->journal->amount_positive;
        $compare = $this->trigger->trigger_value;
        $result  = bccomp($amount, $compare, 4);
        if ($result === -1) {
            // found something
            Log::debug($amount . ' is less than ' . $compare . '. Return true.');

            return true;
        }

        // found nothing.
        Log::debug($amount . ' is not less than ' . $compare . '. Return false.');

        return false;

    }
    
    /**
     * Checks whether this trigger will match all transactions
     * For example: amount > 0 or description starts with ''
     * @return bool
     */
    public function matchesAnything() { return false; }    
}
