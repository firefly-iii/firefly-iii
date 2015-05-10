<?php namespace FireflyIII\Events;

use Illuminate\Queue\SerializesModels;

/**
 * Class JournalDeleted
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Events
 */
class JournalDeleted extends Event
{

    use SerializesModels;

    /**
     * Create a new event instance.
     *
     */
    public function __construct()
    {
        //
    }

}
