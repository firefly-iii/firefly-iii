<?php
/**
 * ListDeviceServerRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Request;

use FireflyIII\Services\Bunq\Object\DeviceServer;
use FireflyIII\Services\Bunq\Token\InstallationToken;
use Illuminate\Support\Collection;

/**
 * Class ListDeviceServerRequest
 *
 * @package FireflyIII\Services\Bunq\Request
 */
class ListDeviceServerRequest extends BunqRequest
{
    /** @var Collection */
    private $devices;
    /** @var  InstallationToken */
    private $installationToken;

    public function __construct()
    {
        parent::__construct();
        $this->devices = new Collection;
    }

    /**
     * @return Collection
     */
    public function getDevices(): Collection
    {
        return $this->devices;
    }


    /**
     *
     */
    public function call(): void
    {
        $uri                                     = '/v1/device-server';
        $data                                    = [];
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->installationToken->getToken();
        $response                                = $this->sendSignedBunqGet($uri, $data, $headers);

        // create device server objects:
        $raw = $this->getArrayFromResponse('DeviceServer', $response);
        /** @var array $entry */
        foreach ($raw as $entry) {
            $this->devices->push(new DeviceServer($entry));
        }

        return;
    }

    /**
     * @param InstallationToken $installationToken
     */
    public function setInstallationToken(InstallationToken $installationToken)
    {
        $this->installationToken = $installationToken;
    }
}
