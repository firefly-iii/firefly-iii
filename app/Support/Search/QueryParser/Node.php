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

use Illuminate\Support\Facades\Log;

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
            // Log::debug(sprintf('This %s is (flipped) now prohibited: %s',get_class($this), var_export(!$this->prohibited, true)));
            return !$this->prohibited;
        }

        // Log::debug(sprintf('This %s is (not flipped) now prohibited: %s',get_class($this), var_export($this->prohibited, true)));
        return $this->prohibited;

    }

    public function equals(Node $compare): bool
    {
        if ($compare->isProhibited(false) !== $this->isProhibited(false)) {
            Log::debug('Return false because prohibited status is different');
            return false;
        }
        if ($compare instanceof NodeGroup) {
            if (count($compare->getNodes()) !== count($this->getNodes())) {
                Log::debug(sprintf('Return false because node count is different. Original is %d, compare is %d', count($this->getNodes()), count($compare->getNodes())));
//                var_dump($this);
//                var_dump($compare);
//                exit;
                return false;
            }
            /**
             * @var int  $index
             * @var Node $node
             */
            foreach ($this->getNodes() as $index => $node) {
                if (false === $node->equals($compare->getNodes()[$index])) {
                    Log::debug('Return false because nodes are different!');
                    var_dump($this);
                    var_dump($compare);
                    exit;
                    return false;
                }
            }
            return true;
        }
        return true;
    }
}
