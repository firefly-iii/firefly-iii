<?php
/**
 * TransactionJournalUpdated.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Events;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

/**
 * Class TransactionJournalUpdated
 *
 * @package FireflyIII\Events
 */
class TransactionJournalUpdated extends Event
{

    use SerializesModels;

    public $journal;

    /**
     * Create a new event instance.
     *
     * @param TransactionJournal $journal
     */
    public function __construct(TransactionJournal $journal)
    {
        //
        $this->journal = $journal;
    }

}
