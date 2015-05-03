<?php namespace FireflyIII\Events;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

/**
 * Class JournalSaved
 *
 * @package FireflyIII\Events
 */
class JournalSaved extends Event
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
