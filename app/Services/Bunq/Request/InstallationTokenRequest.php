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
    /** @var  ServerPublicKey */
    private $serverPublicKey;

    /**
     *
     */
    public function call(): void
    {
        $uri      = '/v1/installation';
        $data     = ['client_public_key' => $this->publicKey,];
        $headers  = $this->getDefaultHeaders();
        $response = [];
        if ($this->fake) {
            $response = json_decode(
                '{"Response":[{"Id":{"id":875936}},{"Token":{"id":13172597,"created":"2017-08-05 11:46:07.061740","updated":"2017-08-05 11:46:07.061740","token":"35278fcc8b0615261fe23285e6d2e6ccd05ac4c93454981bd5e985ec453e5b5d"}},{"ServerPublicKey":{"server_public_key":"-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAon5y6OZGvTN8kIqPBdro\ndG8TWVw6sl34hAWI47NK6Pi7gmnTtd\/k9gfwq56iI4Er8uMM5e4QmjD++XrBIqcw\nHohDVK03li3xsyJPZ4EBSUOkv4VKXKL\/quqlSgDmPnxtT39BowUZl1um5QbTm0hW\npGI\/0bK7jQk7mbEan9yDOpXnczKgfNlo4o+zbFquPdUfA5LE8R8X057dB6ab7eqA\n9Aybo+I6xyrsOOztufg3Yfe5RA6a0Sikqe\/L8HCP+9TJByUI2pwydPou3KONfYhK\n1NQJZ+RCZ6V+jmcuzKe2vq0jhBZd26wNscl48Sm7etJeuBOpHE+MgO24JiTEYlLS\nVQIDAQAB\n-----END PUBLIC KEY-----\n"}}]}',
                true
            );
        }
        if (!$this->fake) {
            $response = $this->sendUnsignedBunqPost($uri, $data, $headers);
        }
        //echo '<hr><pre>' . json_encode($response) . '</pre><hr>';

        $this->installationId    = $this->extractInstallationId($response);
        $this->serverPublicKey   = $this->extractServerPublicKey($response);
        $this->installationToken = $this->extractInstallationToken($response);

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
     * @return ServerPublicKey
     */
    public function getServerPublicKey(): ServerPublicKey
    {
        return $this->serverPublicKey;
    }

    /**
     * @param bool $fake
     */
    public function setFake(bool $fake)
    {
        $this->fake = $fake;
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