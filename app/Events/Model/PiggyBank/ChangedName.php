<?php

namespace FireflyIII\Events\Model\PiggyBank;

use FireflyIII\Events\Event;
use FireflyIII\Models\PiggyBank;
use Illuminate\Queue\SerializesModels;

class ChangedName extends Event
{
    use SerializesModels;

    public function __construct(public PiggyBank $piggyBank, public string $oldName, public string $newName)
    {
    }
}
