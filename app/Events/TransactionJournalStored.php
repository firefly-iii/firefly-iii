<?php
declare(strict_types = 1);
/**
 * TransactionJournalStored.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Events;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

/**
 * Class TransactionJournalStored
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Events
 */
class TransactionJournalStored extends Event
{

    use SerializesModels;

    public $journal;
    public $piggyBankId;

    /**
     * Create a new event instance.
     *
     * @param TransactionJournal $journal
     * @param int                $piggyBankId
     */
    public function __construct(TransactionJournal $journal, int $piggyBankId)
    {
        //
        $this->journal     = $journal;
        $this->piggyBankId = $piggyBankId;

    }

}
