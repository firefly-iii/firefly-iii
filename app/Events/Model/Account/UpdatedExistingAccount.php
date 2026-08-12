<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\Account;

use FireflyIII\Events\Event;
use FireflyIII\Models\Account;
use Illuminate\Queue\SerializesModels;

class UpdatedExistingAccount extends Event
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Account $account,
        public array $oldData
    ) {}
}
