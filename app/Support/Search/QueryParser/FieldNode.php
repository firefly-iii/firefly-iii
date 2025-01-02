<?php

declare(strict_types=1);

namespace FireflyIII\Support\Search\QueryParser;

/**
 * Represents a field operator with value (e.g. amount:100)
 */
class FieldNode extends Node
{
    private string $operator;
    private string $value;

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

    public function __toString(): string
    {
        return ($this->prohibited ? '-' : '') . $this->operator . ':' . $this->value;
    }
}
