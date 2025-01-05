<?php


/*
 * Node.php
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
 * Base class for all nodes
 */
abstract class Node
{
    protected bool $prohibited;

    /**
     * Returns the prohibited status of the node, optionally inverted based on flipFlag
     *
     * Flipping is used when a node is inside a NodeGroup that has a prohibited status itself, causing inversion of the
     * query parts inside
     *
     * @param bool $flipFlag When true, inverts the prohibited status
     *
     * @return bool The (potentially inverted) prohibited status
     */
    public function isProhibited(bool $flipFlag): bool
    {
        if ($flipFlag) {
            return !$this->prohibited;
        }
        return $this->prohibited;

    }
}
