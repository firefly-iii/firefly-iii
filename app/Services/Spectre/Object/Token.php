<?php
/**
 * Token.php
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

namespace FireflyIII\Services\Spectre\Object;

use Carbon\Carbon;

/**
 * Class Token
 */
class Token extends SpectreObject
{
    /** @var string */
    private $connectUrl;
    /** @var Carbon */
    private $expiresAt;
    /** @var string */
    private $token;

    /**
     * Token constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->token      = $data['token'];
        $this->expiresAt  = new Carbon($data['expires_at']);
        $this->connectUrl = $data['connect_url'];
    }

    /**
     * @return string
     */
    public function getConnectUrl(): string
    {
        return $this->connectUrl;
    }

    /**
     * @return Carbon
     */
    public function getExpiresAt(): Carbon
    {
        return $this->expiresAt;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

}