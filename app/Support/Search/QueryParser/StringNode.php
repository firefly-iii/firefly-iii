<?php

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
        $this->value = $value;
        $this->prohibited = $prohibited;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return ($this->prohibited ? '-' : '') . $this->value;
    }
}
