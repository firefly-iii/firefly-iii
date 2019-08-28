<?php
/**
 * IngBelgiumTest.php
 * Copyright (c) 2019 Sander Kleykens <sander@kleykens.com>
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Import\Specifics;


use FireflyIII\Import\Specifics\IngBelgium;
use Log;
use Tests\TestCase;

/**
 * Class IngBelgiumTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IngBelgiumTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * Should return the exact same array.
     *
     * @covers \FireflyIII\Import\Specifics\IngBelgium
     */
    public function testEmptyRow(): void
    {
        $row = [0, 1, 2, 3, 4];

        $parser = new IngBelgium;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);
    }

    /**
     * Data with description and opposing account information.
     *
     * @covers \FireflyIII\Import\Specifics\IngBelgium
     */
    public function testParseDescriptionAndOpposingAccountInformation(): void
    {
        $row = [
            0,
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            'Europese overschrijving                                                         Van: DE H JOHN DOE                                                            De Laan 123                                                         1000        BRUSSEL                                                                                                            België                                                          IBAN: BE01123456789012                                                          Mededeling:                                                                      A random description                                                                                     ',
            10
        ];

        $parser = new IngBelgium;
        $result = $parser->run($row);
        $this->assertEquals($row, array_slice($result, 0, 11));
        $this->assertEquals('DE H JOHN DOE', $result[11]);
        $this->assertEquals('BE01123456789012', $result[12]);
        $this->assertEquals('A random description', $result[13]);
    }

    /**
     * Data with structured description.
     *
     * @covers \FireflyIII\Import\Specifics\IngBelgium
     */
    public function testParseStructuredDescription(): void
    {
        $row = [
            0,
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            'Europese overschrijving                                                         Mededeling:                                                                      ***090/9337/55493***                                                                                     ',
            10
        ];

        $parser = new IngBelgium;
        $result = $parser->run($row);
        $this->assertEquals($row, array_slice($result, 0, 11));
        $this->assertEquals('+++090/9337/55493+++', $result[13]);
    }

    /**
     * Empty transaction details
     *
     * @covers \FireflyIII\Import\Specifics\IngBelgium
     */
    public function testEmptyTransactionDetails(): void
    {
        $row = [0, 1, 2, 3, 4, 5, 6, 7, 8, '', 10];

        $parser = new IngBelgium;
        $result = $parser->run($row);
        $this->assertEquals($row, array_slice($result, 0, 11));
        $this->assertEquals('', $result[11]);
        $this->assertEquals('', $result[12]);
        $this->assertEquals('', $result[13]);
    }
}
