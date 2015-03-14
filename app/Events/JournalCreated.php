<?php namespace FireflyIII\Events;

use FireflyIII\Events\Event;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

class JournalCreated extends Event {

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
        $this->journal = $journal;
        $this->piggyBankId = $piggyBankId;




	}

}
