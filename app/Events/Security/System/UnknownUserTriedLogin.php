<?php




declare(strict_types=1);



namespace FireflyIII\Events\Security\System;

use Illuminate\Queue\SerializesModels;

class UnknownUserTriedLogin
{
    use SerializesModels;

    public function __construct(
        public string $address
    ) {}
}
