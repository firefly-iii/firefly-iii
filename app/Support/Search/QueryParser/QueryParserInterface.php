<?php

declare(strict_types=1);

namespace FireflyIII\Support\Search\QueryParser;

interface QueryParserInterface
{
    /**
     * @return NodeGroup
     */
    public function parse(string $query): NodeGroup;
}
