<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\BudgetLimit;

use Carbon\Carbon;
use FireflyIII\Events\Event;
use FireflyIII\Models\Budget;
use FireflyIII\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DestroyedBudgetLimit extends Event
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public Budget $budget,
        public Carbon $start,
        public Carbon $end,
        public bool $createWebhookMessages
    ) {
        Log::debug(sprintf('DestroyedBudgetLimit(#%d) Event', $budget->id));
    }
}
