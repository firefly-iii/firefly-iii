<?php


/*
 * StringNode.php
 * Copyright (c) 2025 https://github.com/Sobuno
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Search\QueryParser;

/**
 * Represents a string in the search query, meaning either a single-word without spaces or a quote-delimited string
 */
class StringNode extends Node
{
    private string $value;

    public function __construct(string $value, bool $prohibited = false)
    {
        $this->value      = $value;
        $this->prohibited = $prohibited;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
