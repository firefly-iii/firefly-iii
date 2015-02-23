<?php namespace FireflyIII\Events;

use Illuminate\Queue\SerializesModels;

class JournalDeleted extends Event
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

}
