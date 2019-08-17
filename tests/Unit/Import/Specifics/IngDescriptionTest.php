<?php
/**
 * IngDescriptionTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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


use FireflyIII\Import\Specifics\IngDescription;
use Log;
use Tests\TestCase;

/**
 * Class IngDescriptionTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IngDescriptionTest extends TestCase
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
     * Test changes to BA row.
     *
     * Remove specific fields.
     *
     * @covers \FireflyIII\Import\Specifics\IngDescription
     */
    public function testRunBABasic(): void
    {
        $row = [0, 'XX', 2, '', 'BA', 5, 6, 7, 'XX', 9, 10];

        $parser = new IngDescription;
        $result = $parser->run($row);
        $this->assertEquals('XX XX', $result[8]);
    }

    /**
     * Empty description? Use "tegenrekening".
     * Remove specific fields.
     *
     * @covers \FireflyIII\Import\Specifics\IngDescription
     */
    public function testRunEmptyDescr(): void
    {
        $row = [0, 1, 2, '', 'GT', 5, 6, 7, 'Naar Oranje Spaarrekening Bla bla', 9, 10];

        $parser = new IngDescription;
        $result = $parser->run($row);
        $this->assertEquals('Bla bla', $result[3]);
    }

    /**
     * See if the description is removed
     *
     * @covers \FireflyIII\Import\Specifics\IngDescription
     */
    public function testRunGTRemoveDescr(): void
    {
        $iban = 'NL66INGB0665877351';
        $row  = [0, 1, 2, $iban, 'GT', 5, 6, 7, 'Bla bla bla Omschrijving: Should be removed IBAN: ' . $iban, 9, 10];

        $parser = new IngDescription;
        $result = $parser->run($row);
        $this->assertEquals('Should be removed', $result[8]);
    }

    /**
     * Try if the IBAN is removed in GT transactions
     *
     * @covers \FireflyIII\Import\Specifics\IngDescription
     */
    public function testRunGTRemoveIban(): void
    {
        $iban = 'NL66INGB0665877351';
        $row  = [0, 1, 2, $iban, 'GT', 5, 6, 7, 'Should be removed IBAN: ' . $iban, 9, 10];

        $parser = new IngDescription;
        $result = $parser->run($row);
        $this->assertEquals('Should be removed', $result[8]);
    }

    /**
     * Try if the IBAN is removed in IC transactions
     *
     * @covers \FireflyIII\Import\Specifics\IngDescription
     */
    public function testRunICRemoveIban(): void
    {
        $iban = 'NL66INGB0665877351';
        $row  = [0, 1, 2, $iban, 'IC', 5, 6, 7, 'Should be removed IBAN: ' . $iban, 9, 10];

        $parser = new IngDescription;
        $result = $parser->run($row);
        $this->assertEquals('Should be removed', $result[8]);
    }

    /**
     * Try if the IBAN is removed in OV transactions
     *
     * @covers \FireflyIII\Import\Specifics\IngDescription
     */
    public function testRunOVRemoveIban(): void
    {
        $iban = 'NL66INGB0665877351';
        $row  = [0, 1, 2, $iban, 'OV', 5, 6, 7, 'Should be removed IBAN: ' . $iban, 9, 10];

        $parser = new IngDescription;
        $result = $parser->run($row);
        $this->assertEquals('Should be removed', $result[8]);
    }

    /**
     * @covers \FireflyIII\Import\Specifics\IngDescription
     */
    public function testRunShortArray(): void
    {
        $row = [0, 1, 2, 3];

        $parser = new IngDescription;
        $result = $parser->run($row);

        $this->assertEquals($row, $result);
    }

}
