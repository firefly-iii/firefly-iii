<?php

declare(strict_types=1);

namespace Tests\unit\Support\Search\QueryParser;

use FireflyIII\Support\Search\QueryParser\QueryParser;
use FireflyIII\Support\Search\QueryParser\QueryParserInterface;

/**
 * @group unit-test
 * @group support
 * @group search
 *
 * @internal
 *
 * @coversNothing
 */
final class QueryParserParseQueryTest extends AbstractQueryParserInterfaceParseQueryTester
{
    protected function createParser(): QueryParserInterface
    {
        return new QueryParser();
    }
}
