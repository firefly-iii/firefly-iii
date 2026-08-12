<?php




declare(strict_types=1);



namespace FireflyIII\Events\Security\System;

use FireflyIII\Events\Event;
use FireflyIII\Models\InvitedUser;
use Illuminate\Queue\SerializesModels;

class NewInvitationCreated extends Event
{
    use SerializesModels;

    public function __construct(
        public InvitedUser $invitee
    ) {}
}
