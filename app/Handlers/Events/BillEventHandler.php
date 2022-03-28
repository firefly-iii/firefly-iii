<?php

namespace FireflyIII\Handlers\Events;

use FireflyIII\Events\WarnUserAboutBill;
use FireflyIII\Mail\BillWarningMail;
use Log;
use Mail;

/**
 * Class BillEventHandler
 */
class BillEventHandler
{
    /**
     * @param WarnUserAboutBill $event
     * @return void
     */
    public function warnAboutBill(WarnUserAboutBill $event): void
    {
        $bill      = $event->bill;
        $field     = $event->field;
        $diff      = $event->diff;
        $user      = $bill->user;
        $address   = $user->email;
        $ipAddress = request()?->ip();

        // see if user has alternative email address:
        $pref = app('preferences')->getForUser($user, 'remote_guard_alt_email');
        if (null !== $pref) {
            $address = $pref->data;
        }

        // send message:
        Mail::to($address)->send(new BillWarningMail($bill, $field, $diff, $ipAddress));


        Log::debug('warnAboutBill');
    }

}