<?php

namespace FireflyIII\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Log;

/**
 * Class RequestedReportOnJournals
 */
class RequestedReportOnJournals
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var Collection */
    public $journals;
    /** @var int */
    public $userId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $userId, Collection $journals)
    {
        Log::debug('In event RequestedReportOnJournals.');
        $this->userId   = $userId;
        $this->journals = $journals;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
