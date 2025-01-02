<?php

declare(strict_types=1);

namespace Tests\unit\Support\Search\QueryParser;

use FireflyIII\Support\Search\QueryParser\Field;
use FireflyIII\Support\Search\QueryParser\QueryParserInterface;
use FireflyIII\Support\Search\QueryParser\Word;
use FireflyIII\Support\Search\QueryParser\Subquery;
use FireflyIII\Support\Search\QueryParser\Node;
use Tests\integration\TestCase;

abstract class AbstractQueryParserInterfaceParseQueryTest extends TestCase
{
    abstract protected function createParser(): QueryParserInterface;

    public function queryDataProvider(): array
    {
        return [
            'empty query' => [
                'query' => '',
                'expected' => []
            ],
            'simple word' => [
                'query' => 'grocaeries',
                'expected' => [new Word('groceries')]
            ],
            'prohibited word' => [
                'query' => '-groceries',
                'expected' => [new Word('groceries', true)]
            ],
            'prohibited field' => [
                'query' => '-amount:100',
                'expected' => [new Field('amount', '100', true)]
            ],
            'quoted word' => [
                'query' => '"test phrase"',
                'expected' => [new Word('test phrase')]
            ],
            'prohibited quoted word' => [
                'query' => '-"test phrase"',
                'expected' => [new Word('test phrase', true)]
            ],
            'multiple words' => [
                'query' => 'groceries shopping market',
                'expected' => [
                    new Word('groceries'),
                    new Word('shopping'),
                    new Word('market')
                ]
            ],
            'field operator' => [
                'query' => 'amount:100',
                'expected' => [new Field('amount', '100')]
            ],
            'quoted field value with single space' => [
                'query' => 'description:"test phrase"',
                'expected' => [new Field('description', 'test phrase')]
            ],
            'multiple fields' => [
                'query' => 'amount:100 category:food',
                'expected' => [
                    new Field('amount', '100'),
                    new Field('category', 'food')
                ]
            ],
            'simple subquery' => [
                'query' => '(amount:100 category:food)',
                'expected' => [
                    new Subquery([
                        new Field('amount', '100'),
                        new Field('category', 'food')
                    ])
                ]
            ],
            'prohibited subquery' => [
                'query' => '-(amount:100 category:food)',
                'expected' => [
                    new Subquery([
                        new Field('amount', '100'),
                        new Field('category', 'food')
                    ], true)
                ]
            ],
            'nested subquery' => [
                'query' => '(amount:100 (description:"test" category:food))',
                'expected' => [
                    new Subquery([
                        new Field('amount', '100'),
                        new Subquery([
                            new Field('description', 'test'),
                            new Field('category', 'food')
                        ])
                    ])
                ]
            ],
            'mixed words and operators' => [
                'query' => 'groceries amount:50 shopping',
                'expected' => [
                    new Word('groceries'),
                    new Field('amount', '50'),
                    new Word('shopping')
                ]
            ],
            'subquery after field value' => [
                'query' => 'amount:100 (description:"market" category:food)',
                'expected' => [
                    new Field('amount', '100'),
                    new Subquery([
                        new Field('description', 'market'),
                        new Field('category', 'food')
                    ])
                ]
            ],
            'word followed by subquery' => [
                'query' => 'groceries (amount:100 description_contains:"test")',
                'expected' => [
                    new Word('groceries'),
                    new Subquery([
                        new Field('amount', '100'),
                        new Field('description_contains', 'test')
                    ])
                ]
            ],
            'nested subquery with prohibited field' => [
                'query' => '(amount:100 (description_contains:"test payment" -has_attachments:true))',
                'expected' => [
                    new Subquery([
                        new Field('amount', '100'),
                        new Subquery([
                            new Field('description_contains', 'test payment'),
                            new Field('has_attachments', 'true', true)
                        ])
                    ])
                ]
            ],
            'complex nested subqueries' => [
                'query' => 'shopping (amount:50 market (-category:food word description:"test phrase" (has_notes:true)))',
                'expected' => [
                    new Word('shopping'),
                    new Subquery([
                        new Field('amount', '50'),
                        new Word('market'),
                        new Subquery([
                            new Field('category', 'food', true),
                            new Word('word'),
                            new Field('description', 'test phrase'),
                            new Subquery([
                                new Field('has_notes', 'true')
                            ])
                        ])
                    ])
                ]
            ],
            'word with multiple spaces' => [
                'query' => '"multiple   spaces"',
                'expected' => [new Word('multiple   spaces')]
            ],
            'field with multiple spaces in value' => [
                'query' => 'description:"multiple   spaces   here"',
                'expected' => [new Field('description', 'multiple   spaces   here')]
            ],
            'unmatched right parenthesis in word' => [
                'query' => 'test)word',
                'expected' => [new Word('test)word')]
            ],
            'unmatched right parenthesis in field' => [
                'query' => 'description:test)phrase',
                'expected' => [new Field('description', 'test)phrase')]
            ],
            'subquery followed by word' => [
                'query' => '(amount:100 category:food) shopping',
                'expected' => [
                    new Subquery([
                        new Field('amount', '100'),
                        new Field('category', 'food')
                    ]),
                    new Word('shopping')
                ]
            ]
        ];
    }

    /**
     * @dataProvider queryDataProvider
     * @param string $query The query string to parse
     * @param array $expected The expected parse result
     */
    public function testQueryParsing(string $query, array $expected): void
    {
        $result = $this->createParser()->parse($query);

        $this->assertNodesMatch($expected, $result);
    }

    private function assertNodesMatch(array $expected, array $actual): void
    {
        $this->assertCount(count($expected), $actual);

        foreach ($expected as $index => $expectedNode) {
            $actualNode = $actual[$index];
            $this->assertNodeMatches($expectedNode, $actualNode);
        }
    }

    private function assertNodeMatches(Node $expected, Node $actual): void
    {
        $this->assertInstanceOf(get_class($expected), $actual);
        $this->assertEquals($expected->isProhibited(), $actual->isProhibited());

        match (get_class($expected)) {
            Word::class => $this->assertWordMatches($expected, $actual),
            Field::class => $this->assertFieldMatches($expected, $actual),
            Subquery::class => $this->assertSubqueryMatches($expected, $actual),
            default => throw new \InvalidArgumentException(sprintf(
                'Unexpected node type: %s',
                get_class($expected)
            ))
        };
    }

    private function assertWordMatches(Word $expected, Word $actual): void
    {
        $this->assertEquals($expected->getValue(), $actual->getValue());
    }

    private function assertFieldMatches(Field $expected, Field $actual): void
    {
        $this->assertEquals($expected->getOperator(), $actual->getOperator());
        $this->assertEquals($expected->getValue(), $actual->getValue());
    }

    private function assertSubqueryMatches(Subquery $expected, Subquery $actual): void
    {
        $this->assertNodesMatch($expected->getNodes(), $actual->getNodes());
    }

    private function assertIsWord(Node $node, string $expectedValue, bool $prohibited = false): void
    {
        $this->assertInstanceOf(Word::class, $node);
        /** @var Word $node */
        $this->assertEquals($expectedValue, $node->getValue());
        $this->assertEquals($prohibited, $node->isProhibited());
    }

    private function assertIsField(
        Node $node,
        string $expectedOperator,
        string $expectedValue,
        bool $prohibited = false
    ): void {
        $this->assertInstanceOf(Field::class, $node);
        /** @var Field $node */
        $this->assertEquals($expectedOperator, $node->getOperator());
        $this->assertEquals($expectedValue, $node->getValue());
        $this->assertEquals($prohibited, $node->isProhibited());
    }

    private function assertIsSubquery(Node $node, array $expectedNodes, bool $prohibited = false): void
    {
        $this->assertInstanceOf(Subquery::class, $node);
        /** @var Subquery $node */
        $this->assertCount(count($expectedNodes), $node->getNodes());
        $this->assertEquals($prohibited, $node->isProhibited());

        foreach ($expectedNodes as $index => $expected) {
            $actual = $node->getNodes()[$index];
            if ($expected instanceof Word) {
                $this->assertIsWord($actual, $expected->getValue(), $expected->isProhibited());
            } elseif ($expected instanceof Field) {
                $this->assertIsField($actual, $expected->getOperator(), $expected->getValue(), $expected->isProhibited());
            } elseif ($expected instanceof Subquery) {
                $this->assertIsSubquery($actual, $expected->getNodes(), $expected->isProhibited());
            }
        }
    }
}
