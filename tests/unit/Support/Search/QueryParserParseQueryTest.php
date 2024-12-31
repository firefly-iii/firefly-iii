<?php

namespace Tests\unit\Support;

use FireflyIII\Support\Search\QueryParser;
use FireflyIII\Support\Search\QueryParserInterface;
use Tests\unit\Support\AbstractQueryParserInterfaceParseQueryTest;


/**
 * @group unit-test
 * @group support
 * @group navigation
 *
 * @internal
 *
 * @coversNothing
 */
final class QueryParserParseQueryTest extends AbstractQueryParserInterfaceParseQueryTest
{
    protected function createParser(): QueryParserInterface
    {
        return new QueryParser();
    }
}
