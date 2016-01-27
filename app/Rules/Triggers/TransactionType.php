<?php
/**
 * TransactionType.php
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
 * Class TransactionType
 *
 * @package FireflyIII\Rules\Triggers
 */
class TransactionType implements TriggerInterface
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
        $type   = strtolower($this->journal->transactionType->type);
        $search = strtolower($this->trigger->trigger_value);

        if ($type == $search) {
            Log::debug('Journal is of type "' . $type . '" which matches with "' . $search . '". Return true');

            return true;
        }
        Log::debug('Journal is of type "' . $type . '" which does not match with "' . $search . '". Return false');

        return false;
    }
}