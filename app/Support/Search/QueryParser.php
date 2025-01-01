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

    public function parse(string $query): array
    {
        $this->query = $query;
        $this->position = 0;
        return $this->parseQuery();
    }

    private function parseQuery(): array
    {
        $nodes = [];
        $token = $this->buildNextNode();

        while ($token !== null) {
            $nodes[] = $token;
            $token = $this->buildNextNode();
        }

        return $nodes;
    }

    private function buildNextNode(): ?Node
    {
        $tokenUnderConstruction = '';
        $inQuotes = false;
        $fieldName = '';
        $prohibited = false;

        while ($this->position < strlen($this->query)) {
            $char = $this->query[$this->position];

            // If we're in a quoted string, we treat all characters except another quote as ordinary characters
            if ($inQuotes) {
                if($char !== '"') {
                    $tokenUnderConstruction .= $char;
                    $this->position++;
                    continue;
                } else {
                    $this->position++;
                    return $this->createNode($tokenUnderConstruction, $fieldName, $prohibited);
                }
            }

            switch ($char) {
                case '-':
                    if ($tokenUnderConstruction === '') {
                        // A minus sign at the beginning of a token indicates prohibition
                        $prohibited = true;
                    } else {
                        // In any other location, it's just a normal character
                        $tokenUnderConstruction .= $char;
                    }
                    break;

                case '"':
                    if ($tokenUnderConstruction === '') {
                        // A quote sign at the beginning of a token indicates the start of a quoted string
                        $inQuotes = true;
                    } else {
                        // In any other location, it's just a normal character
                        $tokenUnderConstruction .= $char;
                    }
                    break;

                case '(':
                    if ($tokenUnderConstruction === '') {
                        // A left parentheses at the beginning of a token indicates the start of a subquery
                        $this->position++;
                        return new Subquery($this->parseQuery(), $prohibited);
                    } else {
                        // In any other location, it's just a normal character
                        $tokenUnderConstruction .= $char;
                    }
                    break;

                case ')':
                    if ($tokenUnderConstruction !== '') {
                        $this->position++;
                        return $this->createNode($tokenUnderConstruction, $fieldName, $prohibited);
                    }
                    $this->position++;
                    return null;


                case ':':
                    if ($tokenUnderConstruction !== '') {
                        // If we meet a colon with a left-hand side string, we know we're in a field and are about to set up the value
                        $fieldName = $tokenUnderConstruction;
                        $tokenUnderConstruction = '';
                    } else {
                        // In any other location, it's just a normal character
                        $tokenUnderConstruction .= $char;
                    }
                    break;

                case ' ':
                    // A space indicates the end of a token construction if non-empty, otherwise it's just ignored
                    if ($tokenUnderConstruction !== '') {
                        $this->position++;
                        return $this->createNode($tokenUnderConstruction, $fieldName, $prohibited);
                    }
                    break;

                default:
                    $tokenUnderConstruction .= $char;
            }

            $this->position++;
        }

        return $fieldName !== '' || $tokenUnderConstruction !== ''
            ? $this->createNode($tokenUnderConstruction, $fieldName, $prohibited)
            : null;
    }

    private function createNode(string $token, string $fieldName, bool $prohibited): Node
    {
        if (strlen($fieldName) > 0) {
            return new Field(trim($fieldName), trim($token), $prohibited);
        }
        return new Word(trim($token), $prohibited);
    }
}
