<?php




declare(strict_types=1);



namespace FireflyIII\Events\Model\TransactionGroup;

use FireflyIII\Events\Event;

class UserRequestedBatchProcessing extends Event
{
    public TransactionGroupEventObjects $objects;

    public function __construct(
        public TransactionGroupEventFlags $flags
    ) {
        $this->objects = new TransactionGroupEventObjects();
    }
}
