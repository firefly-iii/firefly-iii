<?php
/**
 * Account.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * Class Account
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
        $this->id           = $data['id'];
        $this->loginId      = $data['login_id'];
        $this->currencyCode = $data['currency_code'];
        $this->balance      = $data['balance'];
        $this->name         = $data['name'];
        $this->nature       = $data['nature'];
        $this->createdAt    = new Carbon($data['created_at']);
        $this->updatedAt    = new Carbon($data['updated_at']);

        foreach ($data['extra'] as $key => $value) {
            $this->extra[$key] = $value;
        }
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