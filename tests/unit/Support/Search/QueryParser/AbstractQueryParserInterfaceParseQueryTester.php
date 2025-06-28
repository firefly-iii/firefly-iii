<?php

declare(strict_types=1);

namespace Tests\unit\Support\Search\QueryParser;

use Iterator;
use FireflyIII\Support\Search\QueryParser\FieldNode;
use FireflyIII\Support\Search\QueryParser\QueryParserInterface;
use FireflyIII\Support\Search\QueryParser\StringNode;
use FireflyIII\Support\Search\QueryParser\NodeGroup;
use FireflyIII\Support\Search\QueryParser\Node;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\integration\TestCase;

abstract class AbstractQueryParserInterfaceParseQueryTester extends TestCase
{
    abstract protected function createParser(): QueryParserInterface;

    /**
     * @param string $query    The query string to parse
     * @param Node   $expected The expected parse result
     */
    #[DataProvider('queryDataProvider')]
    public function testQueryParsing(string $query, Node $expected): void
    {
        $actual = $this->createParser()->parse($query);

        $this->assertObjectEquals($expected, $actual);

    }

    public static function queryDataProvider(): Iterator
    {
        yield 'empty query' => [
            '',
            new NodeGroup([]),
        ];

        yield 'simple word' => [
            'groceries',
            new NodeGroup([new StringNode('groceries')]),
        ];

        yield 'prohibited word' => [
            '-groceries',
            new NodeGroup([new StringNode('groceries', true)]),
        ];

        yield 'prohibited field' => [
            '-amount:100',
            new NodeGroup([new FieldNode('amount', '100', true)]),
        ];

        yield 'quoted word' => [
            '"test phrase"',
            new NodeGroup([new StringNode('test phrase')]),
        ];

        yield 'prohibited quoted word' => [
            '-"test phrase"',
            new NodeGroup([new StringNode('test phrase', true)]),
        ];

        yield 'multiple words' => [
            'groceries shopping market',
            new NodeGroup([
                new StringNode('groceries'),
                new StringNode('shopping'),
                new StringNode('market'),
            ]),
        ];

        yield 'field operator' => [
            'amount:100',
            new NodeGroup([new FieldNode('amount', '100')]),
        ];

        yield 'quoted field value with single space' => [
            'description:"test phrase"',
            new NodeGroup([new FieldNode('description', 'test phrase')]),
        ];

        yield 'multiple fields' => [
            'amount:100 category:food',
            new NodeGroup([
                new FieldNode('amount', '100'),
                new FieldNode('category', 'food'),
            ]),
        ];

        yield 'simple subquery' => [
            '(amount:100 category:food)',
            new NodeGroup([
                new NodeGroup([
                    new FieldNode('amount', '100'),
                    new FieldNode('category', 'food'),
                ]),
            ]),
        ];

        yield 'prohibited subquery' => [
            '-(amount:100 category:food)',
            new NodeGroup([
                new NodeGroup([
                    new FieldNode('amount', '100'),
                    new FieldNode('category', 'food'),
                ], true),
            ]),
        ];

        yield 'nested subquery' => [
            '(amount:100 (description:"test" category:food))',
            new NodeGroup([
                new NodeGroup([
                    new FieldNode('amount', '100'),
                    new NodeGroup([
                        new FieldNode('description', 'test'),
                        new FieldNode('category', 'food'),
                    ]),
                ]),
            ]),
        ];

        yield 'mixed words and operators' => [
            'groceries amount:50 shopping',
            new NodeGroup([
                new StringNode('groceries'),
                new FieldNode('amount', '50'),
                new StringNode('shopping'),
            ]),
        ];

        yield 'subquery after field value' => [
            'amount:100 (description:"market" category:food)',
            new NodeGroup([
                new FieldNode('amount', '100'),
                new NodeGroup([
                    new FieldNode('description', 'market'),
                    new FieldNode('category', 'food'),
                ]),
            ]),
        ];

        yield 'word followed by subquery' => [
            'groceries (amount:100 description_contains:"test")',
            new NodeGroup([
                new StringNode('groceries'),
                new NodeGroup([
                    new FieldNode('amount', '100'),
                    new FieldNode('description_contains', 'test'),
                ]),
            ]),
        ];

        yield 'nested subquery with prohibited field' => [
            '(amount:100 (description_contains:"test payment" -has_attachments:true))',
            new NodeGroup([
                new NodeGroup([
                    new FieldNode('amount', '100'),
                    new NodeGroup([
                        new FieldNode('description_contains', 'test payment'),
                        new FieldNode('has_attachments', 'true', true),
                    ]),
                ]),
            ]),
        ];

        yield 'complex nested subqueries' => [
            'shopping (amount:50 market (-category:food word description:"test phrase" (has_notes:true)))',
            new NodeGroup([
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
        ];

        yield 'word with multiple spaces' => [
            '"multiple   spaces"',
            new NodeGroup([new StringNode('multiple   spaces')]),
        ];

        yield 'field with multiple spaces in value' => [
            'description:"multiple   spaces   here"',
            new NodeGroup([new FieldNode('description', 'multiple   spaces   here')]),
        ];

        yield 'unmatched right parenthesis in word' => [
            'test)word',
            new NodeGroup([new StringNode('test)word')]),
        ];

        yield 'unmatched right parenthesis in field' => [
            'description:test)phrase',
            new NodeGroup([new FieldNode('description', 'test)phrase')]),
        ];

        yield 'subquery followed by word' => [
            '(amount:100 category:food) shopping',
            new NodeGroup([
                new NodeGroup([
                    new FieldNode('amount', '100'),
                    new FieldNode('category', 'food'),
                ]),
                new StringNode('shopping'),
            ]),
        ];
    }
}
