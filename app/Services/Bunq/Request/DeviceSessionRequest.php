<?php
/**
 * DeviceSessionRequest.php
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

namespace FireflyIII\Services\Bunq\Request;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Bunq\Id\DeviceSessionId;
use FireflyIII\Services\Bunq\Object\UserCompany;
use FireflyIII\Services\Bunq\Object\UserPerson;
use FireflyIII\Services\Bunq\Token\InstallationToken;
use FireflyIII\Services\Bunq\Token\SessionToken;

/**
 * @deprecated
 * @codeCoverageIgnore
 * Class DeviceSessionRequest.
 */
class DeviceSessionRequest extends BunqRequest
{
    /** @var DeviceSessionId */
    private $deviceSessionId;
    /** @var InstallationToken */
    private $installationToken;
    /** @var SessionToken */
    private $sessionToken;
    /** @var UserCompany */
    private $userCompany;
    /** @var UserPerson */
    private $userPerson;

    /**
     * @throws FireflyException
     */
    public function call(): void
    {
        $uri                                     = 'session-server';
        $data                                    = ['secret' => $this->secret];
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->installationToken->getToken();
        $response                                = $this->sendSignedBunqPost($uri, $data, $headers);

        $this->deviceSessionId = $this->extractDeviceSessionId($response);
        $this->sessionToken    = $this->extractSessionToken($response);
        $this->userPerson      = $this->extractUserPerson($response);
        $this->userCompany     = $this->extractUserCompany($response);

    }

    /**
     * @return DeviceSessionId
     */
    public function getDeviceSessionId(): DeviceSessionId
    {
        return $this->deviceSessionId;
    }

    /**
     * @return SessionToken
     */
    public function getSessionToken(): SessionToken
    {
        return $this->sessionToken;
    }

    /**
     * @return UserCompany
     */
    public function getUserCompany(): UserCompany
    {
        return $this->userCompany;
    }

    /**
     * @return UserPerson
     */
    public function getUserPerson(): UserPerson
    {
        return $this->userPerson;
    }

    /**
     * @param InstallationToken $installationToken
     */
    public function setInstallationToken(InstallationToken $installationToken)
    {
        $this->installationToken = $installationToken;
    }

    /**
     * @param array $response
     *
     * @return DeviceSessionId
     */
    private function extractDeviceSessionId(array $response): DeviceSessionId
    {
        $data            = $this->getKeyFromResponse('Id', $response);
        $deviceSessionId = new DeviceSessionId;
        $deviceSessionId->setId((int)$data['id']);

        return $deviceSessionId;
    }

    /**
     * @param array $response
     *
     * @return SessionToken
     */
    private function extractSessionToken(array $response): SessionToken
    {
        $data = $this->getKeyFromResponse('Token', $response);

        return new SessionToken($data);
    }

    /**
     * @param $response
     *
     * @return UserCompany
     */
    private function extractUserCompany($response): UserCompany
    {
        $data = $this->getKeyFromResponse('UserCompany', $response);

        return new UserCompany($data);
    }

    /**
     * @param $response
     *
     * @return UserPerson
     */
    private function extractUserPerson($response): UserPerson
    {
        $data = $this->getKeyFromResponse('UserPerson', $response);

        return new UserPerson($data);
    }
}
