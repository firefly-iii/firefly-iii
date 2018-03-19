<?php
/**
 * ListPaymentRequest.php
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

namespace FireflyIII\Services\Bunq\Request;

use FireflyIII\Services\Bunq\Object\MonetaryAccountBank;
use FireflyIII\Services\Bunq\Object\Payment;
use FireflyIII\Services\Bunq\Token\SessionToken;
use Illuminate\Support\Collection;


/**
 * Class ListPaymentRequest
 */
class ListPaymentRequest extends BunqRequest
{

    /** @var MonetaryAccountBank */
    private $account;
    /** @var Collection */
    private $payments;
    /** @var SessionToken */
    private $sessionToken;
    /** @var int */
    private $userId = 0;

    /**
     * TODO support pagination.
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function call(): void
    {
        $this->payments                          = new Collection;
        $uri                                     = sprintf('user/%d/monetary-account/%d/payment', $this->userId, $this->account->getId());
        $data                                    = [];
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->sessionToken->getToken();
        $response                                = $this->sendSignedBunqGet($uri, $data, $headers);

        // create payment objects:
        $raw = $this->getArrayFromResponse('Payment', $response);
        foreach ($raw as $entry) {
            $account = new Payment($entry);
            $this->payments->push($account);
        }

        return;

    }

    /**
     * @param MonetaryAccountBank $account
     */
    public function setAccount(MonetaryAccountBank $account): void
    {
        $this->account = $account;
    }

    /**
     * @param SessionToken $sessionToken
     */
    public function setSessionToken(SessionToken $sessionToken): void
    {
        $this->sessionToken = $sessionToken;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
}
