<?php
/**
 * InstallationTokenRequest.php
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

use FireflyIII\Services\Bunq\Id\InstallationId;
use FireflyIII\Services\Bunq\Object\ServerPublicKey;
use FireflyIII\Services\Bunq\Token\InstallationToken;
use Log;

/**
 * Class InstallationTokenRequest
 *
 * @package FireflyIII\Services\Bunq\Request
 */
class InstallationTokenRequest extends BunqRequest
{
    /** @var InstallationId */
    private $installationId;
    /** @var  InstallationToken */
    private $installationToken;
    /** @var string */
    private $publicKey = '';

    /**
     *
     */
    public function call(): void
    {
        $uri      = '/v1/installation';
        $data     = ['client_public_key' => $this->publicKey,];
        $headers  = $this->getDefaultHeaders();
        $response = $this->sendUnsignedBunqPost($uri, $data, $headers);
        Log::debug('Installation request response', $response);

        $this->installationId    = $this->extractInstallationId($response);
        $this->serverPublicKey   = $this->extractServerPublicKey($response);
        $this->installationToken = $this->extractInstallationToken($response);

        Log::debug(sprintf('Installation ID: %s', serialize($this->installationId)));
        Log::debug(sprintf('Installation token: %s', serialize($this->installationToken)));
        Log::debug(sprintf('server public key: %s', serialize($this->serverPublicKey)));

        return;
    }

    /**
     * @return InstallationId
     */
    public function getInstallationId(): InstallationId
    {
        return $this->installationId;
    }

    /**
     * @return InstallationToken
     */
    public function getInstallationToken(): InstallationToken
    {
        return $this->installationToken;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey(string $publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @param array $response
     *
     * @return InstallationId
     */
    private function extractInstallationId(array $response): InstallationId
    {
        $installationId = new InstallationId;
        $data           = $this->getKeyFromResponse('Id', $response);
        $installationId->setId(intval($data['id']));

        return $installationId;
    }

    /**
     * @param array $response
     *
     * @return InstallationToken
     */
    private function extractInstallationToken(array $response): InstallationToken
    {
        $data              = $this->getKeyFromResponse('Token', $response);
        $installationToken = new InstallationToken($data);

        return $installationToken;
    }

    /**
     * @param array $response
     *
     * @return ServerPublicKey
     */
    private function extractServerPublicKey(array $response): ServerPublicKey
    {
        $data            = $this->getKeyFromResponse('ServerPublicKey', $response);
        $serverPublicKey = new ServerPublicKey($data);

        return $serverPublicKey;
    }
}
