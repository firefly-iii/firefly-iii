<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\Budget;

use FireflyIII\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DestroyedBudget extends Event
{
    use SerializesModels;

    public function __construct()
    {
        Log::debug('Created event DestroyedBudget');
    }
}
