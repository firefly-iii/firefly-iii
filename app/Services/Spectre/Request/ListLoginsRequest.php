<?php
/**
 * ListLoginsRequest.php
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

namespace FireflyIII\Services\Spectre\Request;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Login;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ListLoginsRequest
 * @codeCoverageIgnore
 */
class ListLoginsRequest extends SpectreRequest
{
    /** @var Customer */
    private $customer;

    /** @var array */
    private $logins = [];

    /**
     * @throws FireflyException
     *
     */
    public function call(): void
    {
        $hasNextPage = true;
        $nextId      = 0;
        while ($hasNextPage) {
            Log::debug(sprintf('Now calling ListLoginsRequest for next_id %d', $nextId));
            $parameters = ['from_id' => $nextId, 'customer_id' => $this->customer->getId()];
            $uri        = '/api/v4/logins/?' . http_build_query($parameters);
            $response   = $this->sendSignedSpectreGet($uri, []);

            // count entries:
            Log::debug(sprintf('Found %d entries in data-array', count($response['data'])));

            // extract next ID
            $hasNextPage = false;
            if (isset($response['meta']['next_id']) && (int)$response['meta']['next_id'] > $nextId) {
                $hasNextPage = true;
                $nextId      = $response['meta']['next_id'];
                Log::debug(sprintf('Next ID is now %d.', $nextId));
            }
            $collection = new Collection;
            // store logins:
            /** @var array $loginArray */
            foreach ($response['data'] as $loginArray) {
                $collection->push(new Login($loginArray));
            }
            // sort logins by date created:
            $sorted       = $collection->sortByDesc(
                static function (Login $login) {
                    return $login->getUpdatedAt()->timestamp;
                }
            );
            $this->logins = $sorted->toArray();
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
