<?php
/**
 * BunqToken.php
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
