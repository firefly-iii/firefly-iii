<?php

declare(strict_types=1);

/*
 * NotifiesAboutNewInvitation.php
 * Copyright (c) 2026 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Listeners\Security\System;

use Exception;
use FireflyIII\Events\Security\System\NewInvitationCreated;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Mail\InvitationMail;
use FireflyIII\Models\InvitedUser;
use FireflyIII\Notifications\Admin\UserInvitation;
use FireflyIII\Notifications\Notifiables\OwnerNotifiable;
use FireflyIII\Notifications\NotificationSender;
use FireflyIII\Support\Facades\FireflyConfig;
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
        $sendMail = FireflyConfig::get('notification_invite_created', true)->data;
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
