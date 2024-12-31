<?php

namespace Tests\unit\Support;

use FireflyIII\Support\Search\GdbotsQueryParser;
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
final class GdbotsQueryParserParseQueryTest extends AbstractQueryParserInterfaceParseQueryTest
{
    protected function createParser(): QueryParserInterface
    {
        return new GdbotsQueryParser();
    }
}
