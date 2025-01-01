<?php

/*
 * NavigationAddPeriodTest.php
 * Copyright (c) 2023 Antonio Spinelli <https://github.com/tonicospinelli>
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\unit\Support\Search;

use FireflyIII\Support\Search\Field;
use FireflyIII\Support\Search\QueryParserInterface;
use FireflyIII\Support\Search\Word;
use FireflyIII\Support\Search\Subquery;
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
        $this->assertInstanceOf(Field::class, $result[0]);
        /** @var Field $field */
        $field = $result[0];
        $this->assertTrue($field->isProhibited());
        $this->assertEquals('amount', $field->getOperator());
        $this->assertEquals('100', $field->getValue());
    }

    public function testGivenSimpleWordWhenParsingQueryThenReturnsWordNode(): void
    {
        $result = $this->createParser()->parse('groceries');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word */
        $word = $result[0];
        $this->assertEquals('groceries', $word->getValue());
    }

    public function testGivenMultipleWordsWhenParsingQueryThenReturnsWordNodes(): void
    {
        $result = $this->createParser()->parse('groceries shopping market');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word1 */
        $word1 = $result[0];
        $this->assertEquals('groceries', $word1->getValue());

        $this->assertInstanceOf(Word::class, $result[1]);
        /** @var Word $word2 */
        $word2 = $result[1];
        $this->assertEquals('shopping', $word2->getValue());

        $this->assertInstanceOf(Word::class, $result[2]);
        /** @var Word $word3 */
        $word3 = $result[2];
        $this->assertEquals('market', $word3->getValue());
    }

    public function testGivenMixedWordsAndOperatorsWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('groceries amount:50 shopping');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word1 */
        $word1 = $result[0];
        $this->assertEquals('groceries', $word1->getValue());

        $this->assertInstanceOf(Field::class, $result[1]);
        /** @var Field $field */
        $field = $result[1];
        $this->assertEquals('amount', $field->getOperator());
        $this->assertEquals('50', $field->getValue());

        $this->assertInstanceOf(Word::class, $result[2]);
        /** @var Word $word2 */
        $word2 = $result[2];
        $this->assertEquals('shopping', $word2->getValue());
    }

    public function testGivenQuotedValueWithSpacesWhenParsingQueryThenReturnsFieldNode(): void
    {
        $result = $this->createParser()->parse('description_contains:"shopping at market"');

        $this->assertInstanceOf(Field::class, $result[0]);
        /** @var Field $field */
        $field = $result[0];
        $this->assertEquals('description_contains', $field->getOperator());
        $this->assertEquals('shopping at market', $field->getValue());
    }

    public function testGivenSubqueryAfterFieldValueWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('amount:100 (description:"market" category:food)');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertInstanceOf(Field::class, $result[0]);
        /** @var Field $field */
        $field = $result[0];
        $this->assertEquals('amount', $field->getOperator());
        $this->assertEquals('100', $field->getValue());

        $this->assertInstanceOf(Subquery::class, $result[1]);
        /** @var Subquery $subquery */
        $subquery = $result[1];
        $nodes = $subquery->getNodes();
        $this->assertCount(2, $nodes);

        $this->assertInstanceOf(Field::class, $nodes[0]);
        /** @var Field $field1 */
        $field1 = $nodes[0];
        $this->assertEquals('description', $field1->getOperator());
        $this->assertEquals('market', $field1->getValue());

        $this->assertInstanceOf(Field::class, $nodes[1]);
        /** @var Field $field2 */
        $field2 = $nodes[1];
        $this->assertEquals('category', $field2->getOperator());
        $this->assertEquals('food', $field2->getValue());
    }

    public function testGivenWordFollowedBySubqueryWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('groceries (amount:100 description_contains:"test")');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word */
        $word = $result[0];
        $this->assertEquals('groceries', $word->getValue());

        $this->assertInstanceOf(Subquery::class, $result[1]);
        /** @var Subquery $subquery */
        $subquery = $result[1];
        $nodes = $subquery->getNodes();
        $this->assertCount(2, $nodes);

        $this->assertInstanceOf(Field::class, $nodes[0]);
        /** @var Field $field1 */
        $field1 = $nodes[0];
        $this->assertEquals('amount', $field1->getOperator());
        $this->assertEquals('100', $field1->getValue());

        $this->assertInstanceOf(Field::class, $nodes[1]);
        /** @var Field $field2 */
        $field2 = $nodes[1];
        $this->assertEquals('description_contains', $field2->getOperator());
        $this->assertEquals('test', $field2->getValue());
    }

    public function testGivenNestedSubqueryWhenParsingQueryThenReturnsSubqueryNode(): void
    {
        $result = $this->createParser()->parse('(amount:100 (description_contains:"test payment" -has_attachments:true))');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Subquery::class, $result[0]);
        /** @var Subquery $outerSubquery */
        $outerSubquery = $result[0];
        $nodes = $outerSubquery->getNodes();
        $this->assertCount(2, $nodes);

        $this->assertInstanceOf(Field::class, $nodes[0]);
        /** @var Field $field1 */
        $field1 = $nodes[0];
        $this->assertEquals('amount', $field1->getOperator());
        $this->assertEquals('100', $field1->getValue());

        $this->assertInstanceOf(Subquery::class, $nodes[1]);
        /** @var Subquery $innerSubquery */
        $innerSubquery = $nodes[1];
        $subNodes = $innerSubquery->getNodes();
        $this->assertCount(2, $subNodes);

        $this->assertInstanceOf(Field::class, $subNodes[0]);
        /** @var Field $field2 */
        $field2 = $subNodes[0];
        $this->assertEquals('description_contains', $field2->getOperator());
        $this->assertEquals('test payment', $field2->getValue());

        $this->assertInstanceOf(Field::class, $subNodes[1]);
        /** @var Field $field3 */
        $field3 = $subNodes[1];
        $this->assertTrue($field3->isProhibited());
        $this->assertEquals('has_attachments', $field3->getOperator());
        $this->assertEquals('true', $field3->getValue());
    }

    public function testGivenComplexNestedSubqueriesWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('shopping (amount:50 market (-category:food word description:"test phrase" (has_notes:true)))');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word */
        $word = $result[0];
        $this->assertEquals('shopping', $word->getValue());

        $this->assertInstanceOf(Subquery::class, $result[1]);
        /** @var Subquery $firstLevelSubquery */
        $firstLevelSubquery = $result[1];
        $level1Nodes = $firstLevelSubquery->getNodes();
        $this->assertCount(3, $level1Nodes);

        $this->assertInstanceOf(Field::class, $level1Nodes[0]);
        /** @var Field $field1 */
        $field1 = $level1Nodes[0];
        $this->assertEquals('amount', $field1->getOperator());
        $this->assertEquals('50', $field1->getValue());

        $this->assertInstanceOf(Word::class, $level1Nodes[1]);
        /** @var Word $word2 */
        $word2 = $level1Nodes[1];
        $this->assertEquals('market', $word2->getValue());

        $this->assertInstanceOf(Subquery::class, $level1Nodes[2]);
        /** @var Subquery $secondLevelSubquery */
        $secondLevelSubquery = $level1Nodes[2];
        $level2Nodes = $secondLevelSubquery->getNodes();
        $this->assertCount(4, $level2Nodes);

        $this->assertInstanceOf(Field::class, $level2Nodes[0]);
        /** @var Field $field2 */
        $field2 = $level2Nodes[0];
        $this->assertTrue($field2->isProhibited());
        $this->assertEquals('category', $field2->getOperator());
        $this->assertEquals('food', $field2->getValue());

        $this->assertInstanceOf(Word::class, $level2Nodes[1]);
        /** @var Word $word3 */
        $word3 = $level2Nodes[1];
        $this->assertEquals('word', $word3->getValue());

        $this->assertInstanceOf(Field::class, $level2Nodes[2]);
        /** @var Field $field3 */
        $field3 = $level2Nodes[2];
        $this->assertEquals('description', $field3->getOperator());
        $this->assertEquals('test phrase', $field3->getValue());

        $this->assertInstanceOf(Subquery::class, $level2Nodes[3]);
        /** @var Subquery $thirdLevelSubquery */
        $thirdLevelSubquery = $level2Nodes[3];
        $level3Nodes = $thirdLevelSubquery->getNodes();
        $this->assertCount(1, $level3Nodes);

        $this->assertInstanceOf(Field::class, $level3Nodes[0]);
        /** @var Field $field4 */
        $field4 = $level3Nodes[0];
        $this->assertEquals('has_notes', $field4->getOperator());
        $this->assertEquals('true', $field4->getValue());
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
        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word */
        $word = $result[0];
        $this->assertTrue($word->isProhibited());
        $this->assertEquals('test phrase', $word->getValue());
    }

    public function testGivenMultipleFieldsWhenParsingQueryThenReturnsFieldNodes(): void
    {
        $result = $this->createParser()->parse('amount:100 category:food description:"test phrase"');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $this->assertInstanceOf(Field::class, $result[0]);
        /** @var Field $field1 */
        $field1 = $result[0];
        $this->assertEquals('amount', $field1->getOperator());
        $this->assertEquals('100', $field1->getValue());

        $this->assertInstanceOf(Field::class, $result[1]);
        /** @var Field $field2 */
        $field2 = $result[1];
        $this->assertEquals('category', $field2->getOperator());
        $this->assertEquals('food', $field2->getValue());

        $this->assertInstanceOf(Field::class, $result[2]);
        /** @var Field $field3 */
        $field3 = $result[2];
        $this->assertEquals('description', $field3->getOperator());
        $this->assertEquals('test phrase', $field3->getValue());
    }

    public function testGivenProhibitedSubqueryWhenParsingQueryThenReturnsSubqueryNode(): void
    {
        $result = $this->createParser()->parse('-(amount:100 category:food)');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Subquery::class, $result[0]);
        /** @var Subquery $subquery */
        $subquery = $result[0];
        $this->assertTrue($subquery->isProhibited());

        $nodes = $subquery->getNodes();
        $this->assertCount(2, $nodes);

        $this->assertInstanceOf(Field::class, $nodes[0]);
        /** @var Field $field1 */
        $field1 = $nodes[0];
        $this->assertEquals('amount', $field1->getOperator());
        $this->assertEquals('100', $field1->getValue());

        $this->assertInstanceOf(Field::class, $nodes[1]);
        /** @var Field $field2 */
        $field2 = $nodes[1];
        $this->assertEquals('category', $field2->getOperator());
        $this->assertEquals('food', $field2->getValue());
    }

    public function testGivenWordWithMultipleSpacesWhenParsingQueryThenRetainsSpaces(): void
    {
        $result = $this->createParser()->parse('"multiple   spaces"');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word */
        $word = $result[0];
        $this->assertEquals('multiple   spaces', $word->getValue());
    }

    public function testGivenFieldWithMultipleSpacesInValueWhenParsingQueryThenRetainsSpaces(): void
    {
        $result = $this->createParser()->parse('description:"multiple   spaces   here"');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Field::class, $result[0]);
        /** @var Field $field */
        $field = $result[0];
        $this->assertEquals('description', $field->getOperator());
        $this->assertEquals('multiple   spaces   here', $field->getValue());
    }

    public function testGivenUnmatchedRightParenthesisWhenParsingQueryThenTreatsAsCharacter(): void
    {
        $result = $this->createParser()->parse('test)word');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Word::class, $result[0]);
        /** @var Word $word */
        $word = $result[0];
        $this->assertEquals('test)word', $word->getValue());
    }

    public function testGivenUnmatchedRightParenthesisInFieldWhenParsingQueryThenTreatsAsCharacter(): void
    {
        $result = $this->createParser()->parse('description:test)phrase');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Field::class, $result[0]);
        /** @var Field $field */
        $field = $result[0];
        $this->assertEquals('description', $field->getOperator());
        $this->assertEquals('test)phrase', $field->getValue());
    }

    public function testGivenSubqueryFollowedByWordWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('(amount:100 category:food) shopping');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertInstanceOf(Subquery::class, $result[0]);
        /** @var Subquery $subquery */
        $subquery = $result[0];
        $nodes = $subquery->getNodes();
        $this->assertCount(2, $nodes);

        $this->assertInstanceOf(Field::class, $nodes[0]);
        /** @var Field $field1 */
        $field1 = $nodes[0];
        $this->assertEquals('amount', $field1->getOperator());
        $this->assertEquals('100', $field1->getValue());

        $this->assertInstanceOf(Field::class, $nodes[1]);
        /** @var Field $field2 */
        $field2 = $nodes[1];
        $this->assertEquals('category', $field2->getOperator());
        $this->assertEquals('food', $field2->getValue());

        $this->assertInstanceOf(Word::class, $result[1]);
        /** @var Word $word */
        $word = $result[1];
        $this->assertEquals('shopping', $word->getValue());
    }
}
