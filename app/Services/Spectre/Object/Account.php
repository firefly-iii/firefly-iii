<?php
/**
 * Account.php
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
 * Class Account
 *
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Account extends SpectreObject
{
    /** @var float */
    private $balance;
    /** @var Carbon */
    private $createdAt;
    /** @var string */
    private $currencyCode;
    /** @var array */
    private $extra = [];
    /** @var int */
    private $id;
    /** @var int */
    private $loginId;
    /** @var string */
    private $name;
    /** @var string */
    private $nature;
    /** @var Carbon */
    private $updatedAt;

    /**
     * Account constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id           = (int)$data['id'];
        $this->loginId      = $data['login_id'];
        $this->currencyCode = $data['currency_code'];
        $this->balance      = $data['balance'];
        $this->name         = $data['name'];
        $this->nature       = $data['nature'];
        $this->createdAt    = new Carbon($data['created_at']);
        $this->updatedAt    = new Carbon($data['updated_at']);
        $extraArray         = is_array($data['extra']) ? $data['extra'] : [];
        foreach ($extraArray as $key => $value) {
            $this->extra[$key] = $value;
        }
    }

    /**
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNature(): string
    {
        return $this->nature;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'balance'       => $this->balance,
            'created_at'    => $this->createdAt->toIso8601String(),
            'currency_code' => $this->currencyCode,
            'extra'         => $this->extra,
            'id'            => $this->id,
            'login_id'      => $this->loginId,
            'name'          => $this->name,
            'nature'        => $this->nature,
            'updated_at'    => $this->updatedAt->toIso8601String(),
        ];

        return $array;
    }

}
