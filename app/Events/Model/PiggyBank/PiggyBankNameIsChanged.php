<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\PiggyBank;

use FireflyIII\Events\Event;
use FireflyIII\Models\PiggyBank;
use Illuminate\Queue\SerializesModels;

/**
 * Needs to be an event because system needs old value as well as the new value.
 */
class PiggyBankNameIsChanged extends Event
{
    use SerializesModels;

    public function __construct(
        public PiggyBank $piggyBank,
        public string $oldName,
        public string $newName
    ) {}
}
