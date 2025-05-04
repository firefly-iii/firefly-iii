<?php

/*
 * BillWarningMail.php
 * Copyright (c) 2022 james@firefly-iii.org
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

declare(strict_types=1);

namespace FireflyIII\Mail;

use FireflyIII\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class BillWarningMail
 */
class BillWarningMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * ConfirmEmailChangeMail constructor.
     */
    public function __construct(public Bill $bill, public string $field, public int $diff) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $subject = (string) trans(sprintf('email.bill_warning_subject_%s', $this->field), ['diff' => $this->diff, 'name' => $this->bill->name]);
        if (0 === $this->diff) {
            $subject = (string) trans(sprintf('email.bill_warning_subject_now_%s', $this->field), ['diff' => $this->diff, 'name' => $this->bill->name]);
        }

        return $this
            ->markdown('emails.bill-warning')
            ->subject($subject)
        ;
    }
}
