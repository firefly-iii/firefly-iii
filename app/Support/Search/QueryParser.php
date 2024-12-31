<?php

declare(strict_types=1);

namespace FireflyIII\Support\Search;

/**
 * Query parser class
 */
class QueryParser implements QueryParserInterface
{
    private string $query;
    private int $position = 0;

    /**
     * @return Node[]
     */
    public function parse(string $query): array
    {
        $this->query = $query;
        $this->position = 0;
        return $this->parseQuery();
    }

    private function parseQuery(): array
    {
        $nodes = [];

        while ($this->position < strlen($this->query)) {
            $this->skipWhitespace();

            if ($this->position >= strlen($this->query)) {
                break;
            }

            // Handle subquery
            if ($this->query[$this->position] === '(') {
                $nodes[] = $this->parseSubquery();
                continue;
            }

            // Handle field operator
            if ($this->isStartOfField()) {
                $nodes[] = $this->parseField();
                continue;
            }

            // Handle word
            $nodes[] = $this->parseWord();
        }

        return $nodes;
    }

    private function parseSubquery(): Subquery
    {
        $this->position++; // Skip opening parenthesis
        $nodes = [];

        while ($this->position < strlen($this->query)) {
            $this->skipWhitespace();

            if ($this->query[$this->position] === ')') {
                $this->position++; // Skip closing parenthesis
                break;
            }

            if ($this->query[$this->position] === '(') {
                $nodes[] = $this->parseSubquery();
                continue;
            }

            if ($this->isStartOfField()) {
                $nodes[] = $this->parseField();
                continue;
            }

            $nodes[] = $this->parseWord();
        }

        return new Subquery($nodes);
    }

    private function parseField(): Field
    {
        $prohibited = false;
        if ($this->query[$this->position] === '-') {
            $prohibited = true;
            $this->position++;
        }

        $operator = '';
        while ($this->position < strlen($this->query) && $this->query[$this->position] !== ':') {
            $operator .= $this->query[$this->position];
            $this->position++;
        }
        $this->position++; // Skip colon

        $value = '';
        $inQuotes = false;
        while ($this->position < strlen($this->query)) {
            $char = $this->query[$this->position];

            if ($char === '"' && !$inQuotes) {
                $inQuotes = true;
                $this->position++;
                continue;
            }

            if ($char === '"' && $inQuotes) {
                $inQuotes = false;
                $this->position++;
                break;
            }

            if (!$inQuotes && ($char === ' ' || $char === ')')) {
                break;
            }

            $value .= $char;
            $this->position++;
        }

        return new Field(trim($operator), trim($value), $prohibited);
    }

    private function parseWord(): Word
    {
        $word = '';
        while ($this->position < strlen($this->query)) {
            $char = $this->query[$this->position];
            if ($char === ' ' || $char === '(' || $char === ')') {
                break;
            }
            $word .= $char;
            $this->position++;
        }
        return new Word(trim($word));
    }

    private function isStartOfField(): bool
    {
        $pos = $this->position;
        if ($this->query[$pos] === '-') {
            $pos++;
        }

        // Look ahead for a colon that's not inside quotes
        $inQuotes = false;
        while ($pos < strlen($this->query)) {
            if ($this->query[$pos] === '"') {
                $inQuotes = !$inQuotes;
            }
            if ($this->query[$pos] === ':' && !$inQuotes) {
                return true;
            }
            if ($this->query[$pos] === ' ' && !$inQuotes) {
                return false;
            }
            $pos++;
        }
        return false;
    }

    private function skipWhitespace(): void
    {
        while ($this->position < strlen($this->query) && $this->query[$this->position] === ' ') {
            $this->position++;
        }
    }
}
