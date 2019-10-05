<?php
/**
 * OAuthTokenCreatedMail.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Laravel\Passport\Client;


/**
 * Class OAuthTokenCreatedMail
 *
 * @codeCoverageIgnore
 */
class OAuthTokenCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var Client The client */
    public $client;
    /** @var string Email address of admin */
    public $email;
    /** @var string IP address of admin */
    public $ipAddress;

    /**
     * OAuthTokenCreatedMail constructor.
     *
     * @param string $email
     * @param string $ipAddress
     * @param Client $client
     */
    public function __construct(string $email, string $ipAddress, Client $client)
    {
        $this->email     = $email;
        $this->ipAddress = $ipAddress;
        $this->client    = $client;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->view('emails.oauth-client-created-html')->text('emails.oauth-client-created-text')
                    ->subject('A new OAuth client has been created');
    }
}
