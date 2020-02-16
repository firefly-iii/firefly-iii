<?php
/**
 * CreateTokenRequest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Services\Spectre\Request;

use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Token;


/**
 * Class CreateTokenRequest
 * @codeCoverageIgnore
 */
class CreateTokenRequest extends SpectreRequest
{
    /** @var Customer */
    private $customer;

    /** @var Token */
    private $token;

    /** @var string */
    private $uri;

    /**
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function call(): void
    {
        // add mandatory fields to login object
        $data        = [
            'data' => [
                'customer_id'               => $this->customer->getId(),
                'fetch_scopes'              => ['accounts', 'transactions'],
                'daily_refresh'             => true,
                'include_fake_providers'    => true,
                'show_consent_confirmation' => true,
                'credentials_strategy'      => 'ask',
                'return_to'                 => $this->uri,
            ],
        ];
        $uri         = '/api/v4/tokens/create';
        $response    = $this->sendSignedSpectrePost($uri, $data);
        $this->token = new Token($response['data']);
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @param string $uri
     */
    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }


}
