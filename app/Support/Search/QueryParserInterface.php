<?php

declare(strict_types=1);

namespace FireflyIII\Support\Search;

interface QueryParserInterface
{
    /**
     * @return Node[]
     */
    public function parse(string $query): array;
}


/**
 * Base class for all nodes
 */
abstract class Node
{
    abstract public function __toString(): string;
}

/**
 * Represents a word in the search query
 */
class Word extends Node
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

/**
 * Represents a field operator with value (e.g. amount:100)
 */
class Field extends Node
{
    private string $operator;
    private string $value;
    private bool $prohibited;

    public function __construct(string $operator, string $value, bool $prohibited = false)
    {
        $this->operator = $operator;
        $this->value = $value;
        $this->prohibited = $prohibited;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isProhibited(): bool
    {
        return $this->prohibited;
    }

    public function __toString(): string
    {
        return ($this->prohibited ? '-' : '') . $this->operator . ':' . $this->value;
    }
}

/**
 * Represents a subquery (group of nodes)
 */
class Subquery extends Node
{
    /** @var Node[] */
    private array $nodes;

    /**
     * @param Node[] $nodes
     */
    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;
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
        return '(' . implode(' ', array_map(fn($node) => (string)$node, $this->nodes)) . ')';
    }
}
