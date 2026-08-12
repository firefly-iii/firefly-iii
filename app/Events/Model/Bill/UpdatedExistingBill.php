<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\Bill;

use FireflyIII\Events\Event;
use FireflyIII\Models\Bill;
use Illuminate\Queue\SerializesModels;

class UpdatedExistingBill extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Bill $bill,
        public array $oldData
    ) {}
}
