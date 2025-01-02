<?php

declare(strict_types=1);

namespace FireflyIII\Support\Search\QueryParser;

interface QueryParserInterface
{
    /**
     * @return Node[]
     */
    public function parse(string $query): array;
}
