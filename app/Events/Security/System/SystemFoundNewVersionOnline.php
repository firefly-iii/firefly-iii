<?php




declare(strict_types=1);



namespace FireflyIII\Events\Security\System;

use FireflyIII\Events\Event;
use Illuminate\Queue\SerializesModels;

class SystemFoundNewVersionOnline extends Event
{
    use SerializesModels;

    public function __construct(
        public string $message
    ) {}
}
