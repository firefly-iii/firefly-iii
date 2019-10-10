<?php
/**
 * Token.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Spectre\Object;

use Carbon\Carbon;

/**
 * @codeCoverageIgnore
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

    /**
     *
     */
    public function toArray(): array
    {
        return [
            'connect_url' => $this->connectUrl,
            'expires_at'  => $this->expiresAt->toW3cString(),
            'token'       => $this->token,
        ];
    }

}
