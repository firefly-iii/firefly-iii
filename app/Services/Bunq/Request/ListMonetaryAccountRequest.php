<?php
/**
 * ListMonetaryAccountRequest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Request;

use FireflyIII\Services\Bunq\Object\MonetaryAccountBank;
use FireflyIII\Services\Bunq\Token\SessionToken;
use Illuminate\Support\Collection;

/**
 * Class ListMonetaryAccountRequest
 *
 * @package FireflyIII\Services\Bunq\Request
 */
class ListMonetaryAccountRequest extends BunqRequest
{
    /** @var  Collection */
    private $monetaryAccounts;
    /** @var  SessionToken */
    private $sessionToken;
    /** @var int */
    private $userId = 0;

    /**
     *
     */
    public function call(): void
    {
        $this->monetaryAccounts                  = new Collection;
        $uri                                     = sprintf('/v1/user/%d/monetary-account', $this->userId);
        $data                                    = [];
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->sessionToken->getToken();
        $response                                = $this->sendSignedBunqGet($uri, $data, $headers);

        // create device server objects:
        $raw = $this->getArrayFromResponse('MonetaryAccountBank', $response);
        foreach ($raw as $entry) {
            $account = new MonetaryAccountBank($entry);
            $this->monetaryAccounts->push($account);
        }

        return;
    }

    /**
     * @return Collection
     */
    public function getMonetaryAccounts(): Collection
    {
        return $this->monetaryAccounts;
    }

    /**
     * @param SessionToken $sessionToken
     */
    public function setSessionToken(SessionToken $sessionToken)
    {
        $this->sessionToken = $sessionToken;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;
    }
}
