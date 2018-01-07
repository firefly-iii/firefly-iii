<?php
/**
 * CreateTokenRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Spectre\Request;

use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Token;


/**
 * Class CreateTokenRequest
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
     * @throws \FireflyIII\Services\Spectre\Exception\SpectreException
     */
    public function call(): void
    {
        // add mandatory fields to login object
        $data        = [
            'data' => [
                'customer_id'               => $this->customer->getId(),
                'fetch_type'                => 'recent',
                'daily_refresh'             => true,
                'include_fake_providers'    => true,
                'show_consent_confirmation' => true,
                'credentials_strategy'      => 'ask',
                'return_to'                 => $this->uri,
            ],
        ];
        $uri         = '/api/v3/tokens/create';
        $response    = $this->sendSignedSpectrePost($uri, $data);
        $this->token = new Token($response['data']);

        return;
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