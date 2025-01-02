<?php

declare(strict_types=1);

namespace FireflyIII\Support\Search\QueryParser;

/**
 * Base class for all nodes
 */
abstract class Node
{
    protected bool $prohibited;

    public function isProhibited(): bool
    {
        return $this->prohibited;
    }
}
