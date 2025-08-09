<?php

namespace FireflyIII\Events\Model\Bill;

use FireflyIII\Events\Event;
use FireflyIII\Models\Bill;
use Illuminate\Queue\SerializesModels;

class WarnUserAboutOverdueSubscription extends Event
{
    use SerializesModels;

    public function __construct(public Bill $bill, public array $dates) {}

}
