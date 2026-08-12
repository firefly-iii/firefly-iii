<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\BudgetLimit;

use FireflyIII\Events\Event;
use FireflyIII\Models\BudgetLimit;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreatedBudgetLimit extends Event
{
    use SerializesModels;

    public function __construct(
        public BudgetLimit $budgetLimit,
        public bool $createWebhookMessages
    ) {
        Log::debug(sprintf('CreatedNewBudgetLimit(#%d) Event', $budgetLimit->id));
    }
}
