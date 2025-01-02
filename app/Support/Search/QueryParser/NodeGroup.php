<?php

declare(strict_types=1);

namespace FireflyIII\Support\Search\QueryParser;

/**
 * Represents a group of nodes.
 *
 * NodeGroups can be nested inside other NodeGroups, making them subqueries
 */
class NodeGroup extends Node
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
}
