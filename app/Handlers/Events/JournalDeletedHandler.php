<?php namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\JournalDeleted;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Queue\InteractsWithQueue;

class JournalDeletedHandler
{

    /**
     * Create the event handler.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  JournalDeleted $event
     *
     * @return void
     */
    public function handle(JournalDeleted $event)
    {
        //

    }

}
