<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Security\System;

use Exception;
use FireflyIII\Events\Security\System\NewInvitationCreated;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Mail\InvitationMail;
use FireflyIII\Models\InvitedUser;
use FireflyIII\Notifications\Admin\UserInvitation;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Support\Facades\AppConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifiesAboutNewInvitation implements ShouldQueue
{
    public function handle(NewInvitationCreated $event): void
    {
        $this->sendInvitationNotification($event->invitee);
        $this->sendRegistrationInvite($event->invitee);
    }

    private function sendInvitationNotification(InvitedUser $invitee): void
    {
        $sendMail = AppConfiguration::get('notification_invite_created', true)->data;
        if (false === $sendMail) {
            return;
        }

        NotificationSender::send(new OwnerNotifiable(), new UserInvitation($invitee));
    }

    private function sendRegistrationInvite(InvitedUser $invitee): void
    {
        $email = $invitee->email;
        $admin = $invitee->user->email;
        $url   = route('invite', [$invitee->invite_code]);

        try {
            Mail::to($email)->send(new InvitationMail($email, $admin, $url));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            throw new FireflyException($e->getMessage(), 0, $e);
        }
    }
}
