<?php
/**
 * ColumnValue.php
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

namespace FireflyIII\Support\Import\Placeholder;

/**
 * Class ColumnValue
 *
 * @codeCoverageIgnore
 */
class ColumnValue
{
    /** @var int */
    private $mappedValue;
    /** @var string */
    private $originalRole;
    /** @var string */
    private $role;
    /** @var string */
    private $value;

    /**
     * ColumnValue constructor.
     */
    public function __construct()
    {
        $this->mappedValue = 0;
    }

    /**
     * @return int
     */
    public function getMappedValue(): int
    {
        return $this->mappedValue;
    }

    /**
     * @param int $mappedValue
     */
    public function setMappedValue(int $mappedValue): void
    {
        $this->mappedValue = $mappedValue;
    }

    /**
     * @return string
     */
    public function getOriginalRole(): string
    {
        return $this->originalRole;
    }

    /**
     * @param string $originalRole
     */
    public function setOriginalRole(string $originalRole): void
    {
        $this->originalRole = $originalRole;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }


}
