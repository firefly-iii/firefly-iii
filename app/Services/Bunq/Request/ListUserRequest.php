<?php
/**
 * ListUserRequest.php
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

use FireflyIII\Services\Bunq\Object\UserCompany;
use FireflyIII\Services\Bunq\Object\UserLight;
use FireflyIII\Services\Bunq\Object\UserPerson;
use FireflyIII\Services\Bunq\Token\SessionToken;

/**
 * Class ListUserRequest.
 */
class ListUserRequest extends BunqRequest
{
    /** @var SessionToken */
    private $sessionToken;
    /** @var UserCompany */
    private $userCompany;
    /** @var UserLight */
    private $userLight;
    /** @var UserPerson */
    private $userPerson;

    /**
     *
     */
    public function call(): void
    {
        $uri                                     = '/v1/user';
        $data                                    = [];
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->sessionToken->getToken();
        $response                                = $this->sendSignedBunqGet($uri, $data, $headers);

        // create user objects:
        $light             = $this->getKeyFromResponse('UserLight', $response);
        $company           = $this->getKeyFromResponse('UserCompany', $response);
        $person            = $this->getKeyFromResponse('UserPerson', $response);
        $this->userLight   = new UserLight($light);
        $this->userCompany = new UserCompany($company);
        $this->userPerson  = new UserPerson($person);

        return;
    }

    /**
     * @return UserCompany
     */
    public function getUserCompany(): UserCompany
    {
        return $this->userCompany;
    }

    /**
     * @return UserLight
     */
    public function getUserLight(): UserLight
    {
        return $this->userLight;
    }

    /**
     * @return UserPerson
     */
    public function getUserPerson(): UserPerson
    {
        return $this->userPerson;
    }

    /**
     * @param SessionToken $sessionToken
     */
    public function setSessionToken(SessionToken $sessionToken)
    {
        $this->sessionToken = $sessionToken;
    }
}
