<?php
/**
 * DeviceServerRequest.php
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

use FireflyIII\Services\Bunq\Id\DeviceServerId;
use FireflyIII\Services\Bunq\Token\InstallationToken;

/**
 * Class DeviceServerRequest.
 */
class DeviceServerRequest extends BunqRequest
{
    /** @var string */
    private $description = '';
    /** @var DeviceServerId */
    private $deviceServerId;
    /** @var InstallationToken */
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
