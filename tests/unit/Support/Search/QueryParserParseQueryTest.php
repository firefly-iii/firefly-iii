<?php

namespace Tests\unit\Support\Search;

use FireflyIII\Support\Search\QueryParser2;
use FireflyIII\Support\Search\QueryParserInterface;
use Tests\unit\Support\Search\AbstractQueryParserInterfaceParseQueryTest;


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
        return new QueryParser2();
    }
}
