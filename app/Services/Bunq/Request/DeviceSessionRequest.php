<?php
/**
 * DeviceSessionRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Request;


use FireflyIII\Services\Bunq\Id\DeviceSessionId;
use FireflyIII\Services\Bunq\Object\UserCompany;
use FireflyIII\Services\Bunq\Object\UserPerson;
use FireflyIII\Services\Bunq\Token\InstallationToken;
use FireflyIII\Services\Bunq\Token\SessionToken;
use Log;

/**
 * Class DeviceSessionRequest
 *
 * @package FireflyIII\Services\Bunq\Request
 */
class DeviceSessionRequest extends BunqRequest
{
    /** @var  DeviceSessionId */
    private $deviceSessionId;
    /** @var  InstallationToken */
    private $installationToken;
    /** @var  SessionToken */
    private $sessionToken;
    /** @var  UserCompany */
    private $userCompany;
    /** @var  UserPerson */
    private $userPerson;

    /**
     *
     */
    public function call(): void
    {
        $uri                                     = '/v1/session-server';
        $data                                    = ['secret' => $this->secret];
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->installationToken->getToken();
        $response                                = $this->sendSignedBunqPost($uri, $data, $headers);


        $this->deviceSessionId = $this->extractDeviceSessionId($response);
        $this->sessionToken    = $this->extractSessionToken($response);
        $this->userPerson      = $this->extractUserPerson($response);
        $this->userCompany     = $this->extractUserCompany($response);

        Log::debug(sprintf('Session ID: %s', serialize($this->deviceSessionId)));
        Log::debug(sprintf('Session token: %s', serialize($this->sessionToken)));
        Log::debug(sprintf('Session user person: %s', serialize($this->userPerson)));
        Log::debug(sprintf('Session user company: %s', serialize($this->userCompany)));

        return;
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
        $deviceSessionId->setId(intval($data['id']));

        return $deviceSessionId;
    }

    private function extractSessionToken(array $response): SessionToken
    {
        $data         = $this->getKeyFromResponse('Token', $response);
        $sessionToken = new SessionToken($data);

        return $sessionToken;
    }

    /**
     * @param $response
     *
     * @return UserCompany
     */
    private function extractUserCompany($response): UserCompany
    {
        $data        = $this->getKeyFromResponse('UserCompany', $response);
        $userCompany = new UserCompany($data);


        return $userCompany;
    }

    /**
     * @param $response
     *
     * @return UserPerson
     */
    private function extractUserPerson($response): UserPerson
    {
        $data       = $this->getKeyFromResponse('UserPerson', $response);
        $userPerson = new UserPerson($data);


        return $userPerson;
    }


}