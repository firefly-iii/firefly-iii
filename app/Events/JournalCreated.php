<?php namespace FireflyIII\Events;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

/**
 * Class JournalCreated
 *
 * @package FireflyIII\Events
 */
class JournalCreated extends Event
{

    use SerializesModels;

    public $journal;
    public $piggyBankId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TransactionJournal $journal, $piggyBankId)
    {
        //
        $this->journal     = $journal;
        $this->piggyBankId = $piggyBankId;

    }

}
