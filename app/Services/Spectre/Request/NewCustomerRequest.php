<?php
/**
 * NewCustomerRequest.php
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

/**
 * Class NewCustomerRequest
 */
class NewCustomerRequest extends SpectreRequest
{
    /** @var Customer */
    protected $customer;

    /**
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \FireflyIII\Services\Spectre\Exception\SpectreException
     */
    public function call(): void
    {
        $data     = [
            'data' => [
                'identifier' => 'default_ff3_customer',
            ],
        ];
        $uri      = '/api/v3/customers/';
        $response = $this->sendSignedSpectrePost($uri, $data);
        // create customer:
        $this->customer = new Customer($response['data']);

        return;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }
}
