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
        $this->assertTrue($result[0]->isProhibited());
        $this->assertEquals('amount', $result[0]->getOperator());
        $this->assertEquals('100', $result[0]->getValue());
    }

    public function testGivenSimpleWordWhenParsingQueryThenReturnsWordNode(): void
    {
        $result = $this->createParser()->parse('groceries');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Word::class, $result[0]);
        $this->assertEquals('groceries', $result[0]->getValue());
    }

    public function testGivenMultipleWordsWhenParsingQueryThenReturnsWordNodes(): void
    {
        $result = $this->createParser()->parse('groceries shopping market');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $this->assertInstanceOf(Word::class, $result[0]);
        $this->assertEquals('groceries', $result[0]->getValue());

        $this->assertInstanceOf(Word::class, $result[1]);
        $this->assertEquals('shopping', $result[1]->getValue());

        $this->assertInstanceOf(Word::class, $result[2]);
        $this->assertEquals('market', $result[2]->getValue());
    }

    public function testGivenMixedWordsAndOperatorsWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('groceries amount:50 shopping');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $this->assertInstanceOf(Word::class, $result[0]);
        $this->assertEquals('groceries', $result[0]->getValue());

        $this->assertInstanceOf(Field::class, $result[1]);
        $this->assertEquals('amount', $result[1]->getOperator());
        $this->assertEquals('50', $result[1]->getValue());

        $this->assertInstanceOf(Word::class, $result[2]);
        $this->assertEquals('shopping', $result[2]->getValue());
    }

    public function testGivenQuotedValueWithSpacesWhenParsingQueryThenReturnsFieldNode(): void
    {
        $result = $this->createParser()->parse('description_contains:"shopping at market"');

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('description_contains', $result[0]->getOperator());
        $this->assertEquals('shopping at market', $result[0]->getValue());
    }

    public function testGivenDecimalNumberWhenParsingQueryThenReturnsFieldNode(): void
    {
        $result = $this->createParser()->parse('amount:123.45');

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('amount', $result[0]->getOperator());
        $this->assertEquals('123.45', $result[0]->getValue());
    }

    public function testGivenBooleanOperatorWhenParsingQueryThenReturnsFieldNode(): void
    {
        $result = $this->createParser()->parse('has_any_category:true');

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('has_any_category', $result[0]->getOperator());
        $this->assertEquals('true', $result[0]->getValue());
    }

    public function testGivenFieldOperatorWithBlankValueWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('amount:');

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('amount', $result[0]->getOperator());
        $this->assertEquals('', $result[0]->getValue());
    }

    public function testGivenFieldOperatorWithEmptyQuotedStringWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('amount:""');

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('amount', $result[0]->getOperator());
        $this->assertEquals('', $result[0]->getValue());
    }

    public function testGivenUnterminatedQuoteWhenParsingQueryThenHandlesGracefully(): void
    {
        $result = $this->createParser()->parse('description_contains:"unterminated');

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('description_contains', $result[0]->getOperator());
        $this->assertEquals('unterminated', $result[0]->getValue());
    }

    public function testGivenWordFollowedBySubqueryWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('groceries (amount:100 description_contains:"test")');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Test the word node
        $this->assertInstanceOf(Word::class, $result[0]);
        $this->assertEquals('groceries', $result[0]->getValue());

        // Test the subquery node
        $this->assertInstanceOf(Subquery::class, $result[1]);
        $nodes = $result[1]->getNodes();
        $this->assertCount(2, $nodes);

        // Test first field in subquery
        $this->assertInstanceOf(Field::class, $nodes[0]);
        $this->assertEquals('amount', $nodes[0]->getOperator());
        $this->assertEquals('100', $nodes[0]->getValue());

        // Test second field in subquery
        $this->assertInstanceOf(Field::class, $nodes[1]);
        $this->assertEquals('description_contains', $nodes[1]->getOperator());
        $this->assertEquals('test', $nodes[1]->getValue());
    }

    public function testGivenMultipleFieldsWithQuotedValuesWhenParsingQueryThenReturnsFieldNodes(): void
    {
        $result = $this->createParser()->parse('description:"shopping at market" notes:"paid in cash" category:"groceries and food"');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('description', $result[0]->getOperator());
        $this->assertEquals('shopping at market', $result[0]->getValue());

        $this->assertInstanceOf(Field::class, $result[1]);
        $this->assertEquals('notes', $result[1]->getOperator());
        $this->assertEquals('paid in cash', $result[1]->getValue());

        $this->assertInstanceOf(Field::class, $result[2]);
        $this->assertEquals('category', $result[2]->getOperator());
        $this->assertEquals('groceries and food', $result[2]->getValue());
    }

    public function testGivenSubqueryAfterFieldValueWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('amount:100 (description:"market" category:food)');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('amount', $result[0]->getOperator());
        $this->assertEquals('100', $result[0]->getValue());

        $this->assertInstanceOf(Subquery::class, $result[1]);
        $nodes = $result[1]->getNodes();
        $this->assertCount(2, $nodes);

        $this->assertInstanceOf(Field::class, $nodes[0]);
        $this->assertEquals('description', $nodes[0]->getOperator());
        $this->assertEquals('market', $nodes[0]->getValue());

        $this->assertInstanceOf(Field::class, $nodes[1]);
        $this->assertEquals('category', $nodes[1]->getOperator());
        $this->assertEquals('food', $nodes[1]->getValue());
    }

    public function testGivenMultipleFieldsWithQuotedValuesWithoutSpacesWhenParsingQueryThenReturnsFieldNodes(): void
    {
        $result = $this->createParser()->parse('description:"shopping market"category:"groceries"notes:"cash payment"');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('description', $result[0]->getOperator());
        $this->assertEquals('shopping market', $result[0]->getValue());

        $this->assertInstanceOf(Field::class, $result[1]);
        $this->assertEquals('category', $result[1]->getOperator());
        $this->assertEquals('groceries', $result[1]->getValue());

        $this->assertInstanceOf(Field::class, $result[2]);
        $this->assertEquals('notes', $result[2]->getOperator());
        $this->assertEquals('cash payment', $result[2]->getValue());
    }

    public function testGivenStringWithSingleQuoteInMiddleWhenParsingQueryThenReturnsWordNode(): void
    {
        $result = $this->createParser()->parse('stringWithSingle"InMiddle');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Word::class, $result[0]);
        $this->assertEquals('stringWithSingle"InMiddle', $result[0]->getValue());
    }

    public function testGivenWordStartingWithColonWhenParsingQueryThenReturnsWordNode(): void
    {
        $result = $this->createParser()->parse(':startingWithColon');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Word::class, $result[0]);
        $this->assertEquals(':startingWithColon', $result[0]->getValue());
    }

    public function testGivenComplexNestedSubqueriesWhenParsingQueryThenReturnsCorrectNodes(): void
    {
        $result = $this->createParser()->parse('shopping (amount:50 market (-category:food word description:"test phrase" (has_notes:true)))');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Test the first word node
        $this->assertInstanceOf(Word::class, $result[0]);
        $this->assertEquals('shopping', $result[0]->getValue());

        // Test first level subquery
        $this->assertInstanceOf(Subquery::class, $result[1]);
        /** @var Subquery $firstLevelSubquery */
        $firstLevelSubquery = $result[1];
        $level1Nodes = $firstLevelSubquery->getNodes();
        $this->assertCount(3, $level1Nodes);

        // Test field in first level
        $this->assertInstanceOf(Field::class, $level1Nodes[0]);
        $this->assertEquals('amount', $level1Nodes[0]->getOperator());
        $this->assertEquals('50', $level1Nodes[0]->getValue());

        // Test word in first level
        $this->assertInstanceOf(Word::class, $level1Nodes[1]);
        $this->assertEquals('market', $level1Nodes[1]->getValue());

        // Test second level subquery
        $this->assertInstanceOf(Subquery::class, $level1Nodes[2]);
        $level2Nodes = $level1Nodes[2]->getNodes();
        $this->assertCount(4, $level2Nodes);

        // Test prohibited field in second level
        $this->assertInstanceOf(Field::class, $level2Nodes[0]);
        $this->assertTrue($level2Nodes[0]->isProhibited());
        $this->assertEquals('category', $level2Nodes[0]->getOperator());
        $this->assertEquals('food', $level2Nodes[0]->getValue());

        // Test word in second level
        $this->assertInstanceOf(Word::class, $level2Nodes[1]);
        $this->assertEquals('word', $level2Nodes[1]->getValue());

        // Test field with quoted value in second level
        $this->assertInstanceOf(Field::class, $level2Nodes[2]);
        $this->assertEquals('description', $level2Nodes[2]->getOperator());
        $this->assertEquals('test phrase', $level2Nodes[2]->getValue());

        // Test third level subquery
        $this->assertInstanceOf(Subquery::class, $level2Nodes[3]);
        $level3Nodes = $level2Nodes[3]->getNodes();
        $this->assertCount(1, $level3Nodes);

        // Test field in third level
        $this->assertInstanceOf(Field::class, $level3Nodes[0]);
        $this->assertEquals('has_notes', $level3Nodes[0]->getOperator());
        $this->assertEquals('true', $level3Nodes[0]->getValue());
    }
}
