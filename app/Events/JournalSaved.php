<?php namespace FireflyIII\Events;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

class JournalSaved extends Event
{

    use SerializesModels;

    public $journal;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TransactionJournal $journal)
    {
        //
        $this->journal = $journal;
    }

}
