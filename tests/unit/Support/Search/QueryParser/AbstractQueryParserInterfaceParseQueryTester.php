<?php

declare(strict_types=1);

namespace Tests\unit\Support\Search\QueryParser;

use FireflyIII\Support\Search\QueryParser\FieldNode;
use FireflyIII\Support\Search\QueryParser\QueryParserInterface;
use FireflyIII\Support\Search\QueryParser\StringNode;
use FireflyIII\Support\Search\QueryParser\NodeGroup;
use FireflyIII\Support\Search\QueryParser\Node;
use Tests\integration\TestCase;

abstract class AbstractQueryParserInterfaceParseQueryTester extends TestCase
{
    abstract protected function createParser(): QueryParserInterface;

    public static function queryDataProvider(): iterable
    {
        return [
            'empty query'                           => [
                'query'    => '',
                'expected' => new NodeGroup([]),
            ],
            'simple word'                           => [
                'query'    => 'groceries',
                'expected' => new NodeGroup([new StringNode('groceries')]),
            ],
            'prohibited word'                       => [
                'query'    => '-groceries',
                'expected' => new NodeGroup([new StringNode('groceries', true)]),
            ],
            'prohibited field'                      => [
                'query'    => '-amount:100',
                'expected' => new NodeGroup([new FieldNode('amount', '100', true)]),
            ],
            'quoted word'                           => [
                'query'    => '"test phrase"',
                'expected' => new NodeGroup([new StringNode('test phrase')]),
            ],
            'prohibited quoted word'                => [
                'query'    => '-"test phrase"',
                'expected' => new NodeGroup([new StringNode('test phrase', true)]),
            ],
            'multiple words'                        => [
                'query'    => 'groceries shopping market',
                'expected' => new NodeGroup([
                    new StringNode('groceries'),
                    new StringNode('shopping'),
                    new StringNode('market'),
                ]),
            ],
            'field operator'                        => [
                'query'    => 'amount:100',
                'expected' => new NodeGroup([new FieldNode('amount', '100')]),
            ],
            'quoted field value with single space'  => [
                'query'    => 'description:"test phrase"',
                'expected' => new NodeGroup([new FieldNode('description', 'test phrase')]),
            ],
            'multiple fields'                       => [
                'query'    => 'amount:100 category:food',
                'expected' => new NodeGroup([
                    new FieldNode('amount', '100'),
                    new FieldNode('category', 'food'),
                ]),
            ],
            'simple subquery'                       => [
                'query'    => '(amount:100 category:food)',
                'expected' => new NodeGroup([
                    new NodeGroup([
                        new FieldNode('amount', '100'),
                        new FieldNode('category', 'food'),
                    ]),
                ]),
            ],
            'prohibited subquery'                   => [
                'query'    => '-(amount:100 category:food)',
                'expected' => new NodeGroup([
                    new NodeGroup([
                        new FieldNode('amount', '100'),
                        new FieldNode('category', 'food'),
                    ], true),
                ]),
            ],
            'nested subquery'                       => [
                'query'    => '(amount:100 (description:"test" category:food))',
                'expected' => new NodeGroup([
                    new NodeGroup([
                        new FieldNode('amount', '100'),
                        new NodeGroup([
                            new FieldNode('description', 'test'),
                            new FieldNode('category', 'food'),
                        ]),
                    ]),
                ]),
            ],
            'mixed words and operators'             => [
                'query'    => 'groceries amount:50 shopping',
                'expected' => new NodeGroup([
                    new StringNode('groceries'),
                    new FieldNode('amount', '50'),
                    new StringNode('shopping'),
                ]),
            ],
            'subquery after field value'            => [
                'query'    => 'amount:100 (description:"market" category:food)',
                'expected' => new NodeGroup([
                    new FieldNode('amount', '100'),
                    new NodeGroup([
                        new FieldNode('description', 'market'),
                        new FieldNode('category', 'food'),
                    ]),
                ]),
            ],
            'word followed by subquery'             => [
                'query'    => 'groceries (amount:100 description_contains:"test")',
                'expected' => new NodeGroup([
                    new StringNode('groceries'),
                    new NodeGroup([
                        new FieldNode('amount', '100'),
                        new FieldNode('description_contains', 'test'),
                    ]),
                ]),
            ],
            'nested subquery with prohibited field' => [
                'query'    => '(amount:100 (description_contains:"test payment" -has_attachments:true))',
                'expected' => new NodeGroup([
                    new NodeGroup([
                        new FieldNode('amount', '100'),
                        new NodeGroup([
                            new FieldNode('description_contains', 'test payment'),
                            new FieldNode('has_attachments', 'true', true),
                        ]),
                    ]),
                ]),
            ],
            'complex nested subqueries'             => [
                'query'    => 'shopping (amount:50 market (-category:food word description:"test phrase" (has_notes:true)))',
                'expected' => new NodeGroup([
                    new StringNode('shopping'),
                    new NodeGroup([
                        new FieldNode('amount', '50'),
                        new StringNode('market'),
                        new NodeGroup([
                            new FieldNode('category', 'food', true),
                            new StringNode('word'),
                            new FieldNode('description', 'test phrase'),
                            new NodeGroup([
                                new FieldNode('has_notes', 'true'),
                            ]),
                        ]),
                    ]),
                ]),
            ],
            'word with multiple spaces'             => [
                'query'    => '"multiple   spaces"',
                'expected' => new NodeGroup([new StringNode('multiple   spaces')]),
            ],
            'field with multiple spaces in value'   => [
                'query'    => 'description:"multiple   spaces   here"',
                'expected' => new NodeGroup([new FieldNode('description', 'multiple   spaces   here')]),
            ],
            'unmatched right parenthesis in word'   => [
                'query'    => 'test)word',
                'expected' => new NodeGroup([new StringNode('test)word')]),
            ],
            'unmatched right parenthesis in field'  => [
                'query'    => 'description:test)phrase',
                'expected' => new NodeGroup([new FieldNode('description', 'test)phrase')]),
            ],
            'subquery followed by word'             => [
                'query'    => '(amount:100 category:food) shopping',
                'expected' => new NodeGroup([
                    new NodeGroup([
                        new FieldNode('amount', '100'),
                        new FieldNode('category', 'food'),
                    ]),
                    new StringNode('shopping'),
                ]),
            ],
        ];
    }

    /**
     * @dataProvider queryDataProvider
     *
     * @param string $query    The query string to parse
     * @param Node   $expected The expected parse result
     */
    public function testQueryParsing(string $query, Node $expected): void
    {
        $actual = $this->createParser()->parse($query);

        self::assertObjectEquals($expected, $actual);

    }
}
