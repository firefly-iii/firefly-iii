<?php
/**
 * DeviceServerRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Request;

use FireflyIII\Services\Bunq\Id\DeviceServerId;
use FireflyIII\Services\Bunq\Token\InstallationToken;

/**
 * Class DeviceServerRequest
 *
 * @package Bunq\Request
 */
class DeviceServerRequest extends BunqRequest
{
    /** @var string */
    private $description = '';
    /** @var  DeviceServerId */
    private $deviceServerId;
    /** @var  InstallationToken */
    private $installationToken;
    /** @var array */
    private $permittedIps = [];

    /**
     *
     */
    public function call(): void
    {
        $uri                                     = '/v1/device-server';
        $data                                    = ['description' => $this->description, 'secret' => $this->secret, 'permitted_ips' => $this->permittedIps];
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->installationToken->getToken();
        $response                                = $this->sendSignedBunqPost($uri, $data, $headers);
        $deviceServerId                          = new DeviceServerId;
        $deviceServerId->setId(intval($response['Response'][0]['Id']['id']));
        $this->deviceServerId = $deviceServerId;

        return;
    }

    /**
     * @return DeviceServerId
     */
    public function getDeviceServerId(): DeviceServerId
    {
        return $this->deviceServerId;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @param InstallationToken $installationToken
     */
    public function setInstallationToken(InstallationToken $installationToken)
    {
        $this->installationToken = $installationToken;
    }

    /**
     * @param array $permittedIps
     */
    public function setPermittedIps(array $permittedIps)
    {
        $this->permittedIps = $permittedIps;
    }
}