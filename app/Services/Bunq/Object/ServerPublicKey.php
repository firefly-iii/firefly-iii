<?php
/**
 * ServerPublicKey.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;

/**
 * Class ServerPublicKey
 *
 * @package Bunq\Object
 */
class ServerPublicKey extends BunqObject
{
    /** @var string */
    private $publicKey = '';

    /**
     * ServerPublicKey constructor.
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->publicKey = $response['server_public_key'];
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


}