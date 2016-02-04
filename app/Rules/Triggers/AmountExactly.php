<?php
/**
 * AmountExactly.php
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
 * Class AmountExactly
 *
 * @package FireflyIII\Rules\Triggers
 */
class AmountExactly implements TriggerInterface
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
        if ($result === 0) {
            // found something
            Log::debug($amount . ' is exactly ' . $compare . '. Return true.');

            return true;
        }

        // found nothing.
        Log::debug($amount . ' is not exactly ' . $compare . '. Return false.');

        return false;

    }
}
