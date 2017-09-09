<?php
/**
 * ListMonetaryAccountRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
