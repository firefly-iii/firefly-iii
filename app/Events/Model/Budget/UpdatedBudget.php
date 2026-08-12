<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\Budget;

use FireflyIII\Events\Event;
use FireflyIII\Models\Budget;
use Illuminate\Queue\SerializesModels;

class UpdatedBudget extends Event
{
    use SerializesModels;

    public function __construct(
        public Budget $budget,
        public bool $createWebhookMessages
    ) {}
}
