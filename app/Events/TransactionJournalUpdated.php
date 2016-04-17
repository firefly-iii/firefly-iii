<?php
declare(strict_types = 1);

namespace FireflyIII\Events;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;
use Log;

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
        Log::debug('Created new TransactionJournalUpdated');
        //
        $this->journal = $journal;
    }

}
