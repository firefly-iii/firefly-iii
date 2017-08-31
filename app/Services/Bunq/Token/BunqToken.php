<?php
/**
 * BunqToken.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Token;


use Carbon\Carbon;

/**
 * Class BunqToken
 *
 * @package Bunq\Token
 */
class BunqToken
{
    /** @var  Carbon */
    private $created;
    /** @var int */
    private $id = 0;
    /** @var string */
    private $token = '';
    /** @var  Carbon */
    private $updated;

    /**
     * BunqToken constructor.
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->makeTokenFromResponse($response);
    }

    /**
     * @return Carbon
     */
    public function getCreated(): Carbon
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return Carbon
     */
    public function getUpdated(): Carbon
    {
        return $this->updated;
    }

    /**
     * @param array $response
     */
    protected function makeTokenFromResponse(array $response): void
    {
        $this->id      = $response['id'];
        $this->created = Carbon::createFromFormat('Y-m-d H:i:s.u', $response['created']);
        $this->updated = Carbon::createFromFormat('Y-m-d H:i:s.u', $response['updated']);
        $this->token   = $response['token'];

        return;
    }

}
