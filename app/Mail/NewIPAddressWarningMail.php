<?php
/*
 * NewIPAddressWarningMail.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Mail;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Laravel\Passport\Client;

/**
 * Class NewIPAddressWarningMail
 */
class NewIPAddressWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $ipAddress;
    public string $time;
    public string $host;
    /**
     * OAuthTokenCreatedMail constructor.
     *
     * @param string $ipAddress
     */
    public function __construct(string $ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        // time
        $this->time = now()->formatLocalized((string)trans('config.date_time'));
        $this->host = '';
        $host = gethostbyaddr($this->ipAddress);
        if($host !== $this->ipAddress) {
            $this->host = $host;
        }

        return $this->view('emails.new-ip-html')->text('emails.new-ip-text')
                    ->subject((string) trans('email.login_from_new_ip'));
    }
}