<?php

/*
 * NewIPAddressWarningMail.php
 * Copyright (c) 2021 james@firefly-iii.org
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

use FireflyIII\Exceptions\FireflyException;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class NewIPAddressWarningMail
 */
class NewIPAddressWarningMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $host;
    public string $time;

    /**
     * OAuthTokenCreatedMail constructor.
     */
    public function __construct(public string $ipAddress)
    {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $this->time = now(config('app.timezone'))->isoFormat((string) trans('config.date_time_js'));
        $this->host = '';

        try {
            $hostName = app('steam')->getHostName($this->ipAddress);
        } catch (FireflyException $e) {
            app('log')->error($e->getMessage());
            $hostName = $this->ipAddress;
        }
        if ($hostName !== $this->ipAddress) {
            $this->host = $hostName;
        }

        return $this
            ->markdown('emails.new-ip')
            ->subject((string) trans('email.login_from_new_ip'))
        ;
    }
}
