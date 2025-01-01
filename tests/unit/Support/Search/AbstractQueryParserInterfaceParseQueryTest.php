<?php

declare(strict_types=1);

namespace Tests\unit\Support\Search;

use FireflyIII\Support\Search\Field;
use FireflyIII\Support\Search\QueryParserInterface;
use FireflyIII\Support\Search\Word;
use FireflyIII\Support\Search\Subquery;
use FireflyIII\Support\Search\Node;
use Tests\integration\TestCase;

abstract class AbstractQueryParserInterfaceParseQueryTest extends TestCase
{
    abstract protected function createParser(): QueryParserInterface;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    public function testGivenEmptyStringWhenParsingQueryThenReturnsEmptyArray(): void
    {
        $result = $this->createParser()->parse('');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGivenProhibitedFieldOperatorWhenParsingQueryThenReturnsFieldNode(): void
    {
        $result = $this->createParser()->parse('-amount:100');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsField($result[0], 'amount', '100', true);
    }

    public function testGivenSimpleWordWhenParsingQueryThenReturnsWordNode(): void
    {
        $result = $this->createParser()->parse('groceries');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsWord($result[0], 'groceries');
    }

    public function testGivenMultipleWordsWhenParsingQueryThenReturnsWordNodes(): void
    {
        $result = $this->createParser()->parse('groceries shopping market');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertIsWord($result[0], 'groceries');
        $this->assertIsWord($result[1], 'shopping');
        $this->assertIsWord($result[2], 'market');
    }

    public function testGivenMixedWordsAndOperatorsWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('groceries amount:50 shopping');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertIsWord($result[0], 'groceries');
        $this->assertIsField($result[1], 'amount', '50');
        $this->assertIsWord($result[2], 'shopping');
    }

    public function testGivenQuotedValueWithSpacesWhenParsingQueryThenReturnsFieldNode(): void
    {
        $result = $this->createParser()->parse('description_contains:"shopping at market"');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsField($result[0], 'description_contains', 'shopping at market');
    }

    public function testGivenSubqueryAfterFieldValueWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('amount:100 (description:"market" category:food)');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsField($result[0], 'amount', '100');

        $expectedNodes = [
            new Field('description', 'market'),
            new Field('category', 'food')
        ];
        $this->assertIsSubquery($result[1], $expectedNodes);
    }

    public function testGivenWordFollowedBySubqueryWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('groceries (amount:100 description_contains:"test")');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertIsWord($result[0], 'groceries');

        $expectedNodes = [
            new Field('amount', '100'),
            new Field('description_contains', 'test')
        ];
        $this->assertIsSubquery($result[1], $expectedNodes);
    }

    public function testGivenNestedSubqueryWhenParsingQueryThenReturnsSubqueryNode(): void
    {
        $result = $this->createParser()->parse('(amount:100 (description_contains:"test payment" -has_attachments:true))');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $innerSubqueryNodes = [
            new Field('description_contains', 'test payment'),
            new Field('has_attachments', 'true', true)
        ];
        $outerSubqueryNodes = [
            new Field('amount', '100'),
            new Subquery($innerSubqueryNodes)
        ];
        $this->assertIsSubquery($result[0], $outerSubqueryNodes);
    }

    public function testGivenComplexNestedSubqueriesWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('shopping (amount:50 market (-category:food word description:"test phrase" (has_notes:true)))');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertIsWord($result[0], 'shopping');

        $expectedLevel2 = [
            new Field('category', 'food', true),
            new Word('word'),
            new Field('description', 'test phrase'),
            new Subquery([new Field('has_notes', 'true')])
        ];

        $expectedLevel1 = [
            new Field('amount', '50'),
            new Word('market'),
            new Subquery($expectedLevel2)
        ];

        $this->assertIsSubquery($result[1], $expectedLevel1);
    }

    public function testGivenProhibitedWordWhenParsingQueryThenReturnsWordNode(): void
    {
        $result = $this->createParser()->parse('-groceries');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word */
        $word = $result[0];
        $this->assertTrue($word->isProhibited());
        $this->assertEquals('groceries', $word->getValue());
    }

    public function testGivenQuotedWordWhenParsingQueryThenReturnsWordNode(): void
    {
        $result = $this->createParser()->parse('"test phrase"');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word */
        $word = $result[0];
        $this->assertEquals('test phrase', $word->getValue());
    }

    public function testGivenProhibitedQuotedWordWhenParsingQueryThenReturnsWordNode(): void
    {
        $result = $this->createParser()->parse('-"test phrase"');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsWord($result[0], 'test phrase', true);
    }

    public function testGivenMultipleFieldsWhenParsingQueryThenReturnsFieldNodes(): void
    {
        $result = $this->createParser()->parse('amount:100 category:food description:"test phrase"');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertIsField($result[0], 'amount', '100');
        $this->assertIsField($result[1], 'category', 'food');
        $this->assertIsField($result[2], 'description', 'test phrase');
    }

    public function testGivenProhibitedSubqueryWhenParsingQueryThenReturnsSubqueryNode(): void
    {
        $result = $this->createParser()->parse('-(amount:100 category:food)');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $expectedNodes = [
            new Field('amount', '100'),
            new Field('category', 'food')
        ];
        $this->assertIsSubquery($result[0], $expectedNodes, true);
    }

    public function testGivenWordWithMultipleSpacesWhenParsingQueryThenRetainsSpaces(): void
    {
        $result = $this->createParser()->parse('"multiple   spaces"');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsWord($result[0], 'multiple   spaces');
    }

    public function testGivenFieldWithMultipleSpacesInValueWhenParsingQueryThenRetainsSpaces(): void
    {
        $result = $this->createParser()->parse('description:"multiple   spaces   here"');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsField($result[0], 'description', 'multiple   spaces   here');
    }

    public function testGivenUnmatchedRightParenthesisWhenParsingQueryThenTreatsAsCharacter(): void
    {
        $result = $this->createParser()->parse('test)word');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsWord($result[0], 'test)word');
    }

    public function testGivenUnmatchedRightParenthesisInFieldWhenParsingQueryThenTreatsAsCharacter(): void
    {
        $result = $this->createParser()->parse('description:test)phrase');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsField($result[0], 'description', 'test)phrase');
    }

    public function testGivenSubqueryFollowedByWordWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('(amount:100 category:food) shopping');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $expectedNodes = [
            new Field('amount', '100'),
            new Field('category', 'food')
        ];
        $this->assertIsSubquery($result[0], $expectedNodes);
        $this->assertIsWord($result[1], 'shopping');
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
