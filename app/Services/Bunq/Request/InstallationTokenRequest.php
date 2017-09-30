<?php
/**
 * InstallationTokenRequest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
