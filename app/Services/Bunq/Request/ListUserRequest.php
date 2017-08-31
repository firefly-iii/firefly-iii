<?php
/**
 * ListUserRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Request;

use FireflyIII\Services\Bunq\Object\UserCompany;
use FireflyIII\Services\Bunq\Object\UserLight;
use FireflyIII\Services\Bunq\Object\UserPerson;
use FireflyIII\Services\Bunq\Token\SessionToken;

/**
 * Class ListUserRequest
 *
 * @package FireflyIII\Services\Bunq\Request
 */
class ListUserRequest extends BunqRequest
{
    /** @var  SessionToken */
    private $sessionToken;
    /** @var  UserCompany */
    private $userCompany;
    /** @var  UserLight */
    private $userLight;
    /** @var  UserPerson */
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
