<?php
/**
 * ReportNewJournalsMail.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Class ReportNewJournalsMail.
 *
 * Sends a list of newly created journals to the user.
 *
 * @codeCoverageIgnore
 */
class ReportNewJournalsMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string Email address of the user */
    public $email;
    /** @var string IP address of user (if known) */
    public $ipAddress;

    /** @var Collection A collection of journals */
    public $journals;

    /**
     * ConfirmEmailChangeMail constructor.
     *
     * @param string     $email
     * @param string     $ipAddress
     * @param Collection $journals
     */
    public function __construct(string $email, string $ipAddress, Collection $journals)
    {
        $this->email     = $email;
        $this->ipAddress = $ipAddress;
        $this->journals  = $journals;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $subject = 1 === $this->journals->count()
            ? 'Firefly III has created a new transaction'
            : sprintf(
                'Firefly III has created new %d transactions', $this->journals->count()
            );

        return $this->view('emails.report-new-journals-html')->text('emails.report-new-journals-text')
                    ->subject($subject);
    }
}
