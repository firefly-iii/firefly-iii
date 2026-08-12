<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\Budget;

use FireflyIII\Events\Event;
use FireflyIII\Models\Budget;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DestroyingBudget extends Event
{
    use SerializesModels;

    public function __construct(
        public Budget $budget
    ) {
        Log::debug(sprintf('Created event DestroyingBudget(#%d)', $budget->id));
    }
}
