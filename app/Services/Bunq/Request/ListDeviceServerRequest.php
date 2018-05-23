<?php
/**
 * ListDeviceServerRequest.php
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

use FireflyIII\Services\Bunq\Object\DeviceServer;
use FireflyIII\Services\Bunq\Token\InstallationToken;
use Illuminate\Support\Collection;
use Log;

/**
 * @deprecated
 * @codeCoverageIgnore
 * Class ListDeviceServerRequest.
 */
class ListDeviceServerRequest extends BunqRequest
{
    /** @var Collection */
    private $devices;
    /** @var InstallationToken */
    private $installationToken;

    public function __construct()
    {
        parent::__construct();
        $this->devices = new Collection;
        Log::debug('Constructed ListDeviceServerRequest');
    }

    /**
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function call(): void
    {
        Log::debug('Now in ListDeviceServerRequest::call()');
        $uri                                     = 'device-server';
        $data                                    = [];
        $headers                                 = $this->getDefaultHeaders();
        $headers['X-Bunq-Client-Authentication'] = $this->installationToken->getToken();
        $response                                = $this->sendSignedBunqGet($uri, $data, $headers);
        Log::debug('Returned from sending device-server list request!');
        // create device server objects:
        $raw = $this->getArrayFromResponse('DeviceServer', $response);
        Log::debug(sprintf('Count %d entries in response array.', \count($raw)));
        Log::debug('Full response', $response);
        /** @var array $entry */
        foreach ($raw as $entry) {
            $server = new DeviceServer($entry);
            Log::debug(sprintf('Created server "%s" with IP "%s"', $server->getId()->getId(), $server->getIp()));
            $this->devices->push($server);
        }

    }

    /**
     * @return Collection
     */
    public function getDevices(): Collection
    {
        return $this->devices;
    }

    /**
     * @param InstallationToken $installationToken
     */
    public function setInstallationToken(InstallationToken $installationToken)
    {
        $this->installationToken = $installationToken;
    }
}
