<?php
/**
 * Customer.php
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

/**
 * Class Customer
 *
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Customer extends SpectreObject
{
    /** @var int */
    private $id;
    /** @var string */
    private $identifier;
    /** @var string */
    private $secret;

    /**
     * Customer constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id         = (int)$data['id'];
        $this->identifier = $data['identifier'];
        $this->secret     = $data['secret'];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'identifier' => $this->identifier,
            'secret'     => $this->secret,
        ];
    }
}
