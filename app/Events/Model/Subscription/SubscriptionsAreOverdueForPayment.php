<?php




declare(strict_types=1);

namespace FireflyIII\Events\Model\Subscription;

use FireflyIII\Events\Event;
use FireflyIII\User;
use Illuminate\Queue\SerializesModels;

class SubscriptionsAreOverdueForPayment extends Event
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public array $overdue
    ) {}
}
