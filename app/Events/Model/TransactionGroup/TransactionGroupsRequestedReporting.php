<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\TransactionGroup;

use FireflyIII\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TransactionGroupsRequestedReporting extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $userId,
        public Collection $groups
    ) {}
}
