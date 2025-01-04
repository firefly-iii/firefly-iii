<?php

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
     * Flipping is used when a node is inside a NodeGroup that has a prohibited status itself, causing inversion of the query parts inside
     *
     * @param bool $flipFlag When true, inverts the prohibited status
     * @return bool The (potentially inverted) prohibited status
     */
    public function isProhibited(bool $flipFlag): bool
    {
        if ($flipFlag === true) {
            return !$this->prohibited;
        } else {
            return $this->prohibited;
        }

    }
}
