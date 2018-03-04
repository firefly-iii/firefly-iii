<?php
/**
 * ListLoginsRequest.php
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

namespace FireflyIII\Services\Spectre\Request;

use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Login;
use Log;

/**
 * Class ListLoginsRequest
 */
class ListLoginsRequest extends SpectreRequest
{
    /** @var Customer */
    private $customer;

    /** @var array */
    private $logins = [];

    /**
     *
     */
    public function call(): void
    {
        $hasNextPage = true;
        $nextId      = 0;
        while ($hasNextPage) {
            Log::debug(sprintf('Now calling ListLoginsRequest for next_id %d', $nextId));
            $parameters = ['from_id' => $nextId, 'customer_id' => $this->customer->getId()];
            $uri        = '/api/v3/logins/?' . http_build_query($parameters);
            $response   = $this->sendSignedSpectreGet($uri, []);

            // count entries:
            Log::debug(sprintf('Found %d entries in data-array', count($response['data'])));

            // extract next ID
            $hasNextPage = false;
            if (isset($response['meta']['next_id']) && intval($response['meta']['next_id']) > $nextId) {
                $hasNextPage = true;
                $nextId      = $response['meta']['next_id'];
                Log::debug(sprintf('Next ID is now %d.', $nextId));
            } else {
                Log::debug('No next page.');
            }

            // store logins:
            /** @var array $loginArray */
            foreach ($response['data'] as $loginArray) {
                $this->logins[] = new Login($loginArray);
            }
        }
    }

    /**
     * @return array
     */
    public function getLogins(): array
    {
        return $this->logins;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }


}