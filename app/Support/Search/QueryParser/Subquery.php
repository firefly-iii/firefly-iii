<?php

declare(strict_types=1);

namespace FireflyIII\Support\Search\QueryParser;

/**
 * Represents a subquery (group of nodes)
 */
class Subquery extends Node
{
    /** @var Node[] */
    private array $nodes;

    /**
     * @param Node[] $nodes
     * @param bool $prohibited
     */
    public function __construct(array $nodes, bool $prohibited = false)
    {
        $this->nodes = $nodes;
        $this->prohibited = $prohibited;
    }

    /**
     * @return Node[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }


    public function __toString(): string
    {
        return ($this->prohibited ? '-' : '') . '[' . implode(' ', array_map(fn($node) => (string)$node, $this->nodes)) . ']';
    }
}
