<?php
/**
 * AbnAmroDescriptionTest.php
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


use FireflyIII\Import\Specifics\AbnAmroDescription;
use Log;
use Tests\TestCase;

/**
 * Class AbnAmroDescriptionTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbnAmroDescriptionTest extends TestCase
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
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testEmptyRow(): void
    {
        $row = [1, 2, 3, 4];

        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);
    }

    /**
     * Data that cannot be parsed.
     *
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testParseABN(): void
    {
        $row = [0, 1, 2, 3, 4, 5, 6, 'ABN AMRO 12345678901234567890ABC SomeOtherDescr', ''];

        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals('SomeOtherDescr', $result[7]);
        $this->assertEquals('ABN AMRO', $result[8]);
    }

    /**
     * GEA
     *
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testParseGea(): void
    {
        $row = [0, 1, 2, 3, 4, 5, 6, 'BEA: GEA   NR:00AJ01   31.01.01/19.54 Van HarenSchoenen132 UDE,PAS333', ''];

        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals('Van HarenSchoenen132 UDE', $result[8]);
        $this->assertEquals('GEA Van HarenSchoenen132 UDE', $result[7]);
    }

    /**
     * Gea bea
     *
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testParseGeaBea(): void
    {
        $row = [0, 1, 2, 3, 4, 5, 6, 'BEA: BEA   NR:00AJ01   31.01.01/19.54 Van HarenSchoenen132 UDE,PAS333', ''];

        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals('Van HarenSchoenen132 UDE', $result[8]);
        $this->assertEquals('Van HarenSchoenen132 UDE', $result[7]);
    }

    /**
     * Data that cannot be parsed.
     *
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testParseUnknown(): void
    {
        $row = [0, 1, 2, 3, 4, 5, 6, 'Blabla', ''];

        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals('Unknown', $result[8]);
    }

    /**
     * Basic SEPA data.
     *
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testSepaBasic(): void
    {
        $row    = [0, 1, 2, 3, 4, 5, 6,
                   'SEPA PLAIN:    SEPA iDEAL                       IBAN: NL12RABO0121212212        BIC: RABONL2U                    Naam: Silver Ocean B.V.         Omschrijving: 1232138 1232131233 412321 iBOOD.com iBOOD.com B.V. Kenmerk: 12-12-2014 21:03 002000 0213123238',
                   '', ''];
        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals('1232138 1232131233 412321 iBOOD.com iBOOD.com B.V.', $result[7]);
        $this->assertEquals('Silver Ocean B.V.', $result[8]);
        $this->assertEquals('NL12RABO0121212212', $result[9]);
    }

    /**
     * Basic SEPA data.
     *
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testSepaBasicNoDescription(): void
    {
        $row    = [0, 1, 2, 3, 4, 5, 6,
                   'SEPA PLAIN:    SEPA iDEAL                       IBAN: NL12RABO0121212212        BIC: RABONL2U                    Naam: Silver Ocean B.V.         Omschrijving: Kenmerk: 12-12-2014 21:03 002000 0213123238',
                   '', ''];
        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals(' PLAIN:    SEPA iDEAL        - Silver Ocean B.V. (12-12-2014)', $result[7]);
        $this->assertEquals('Silver Ocean B.V.', $result[8]);
        $this->assertEquals('NL12RABO0121212212', $result[9]);
    }

    /**
     * Basic TRTP data.
     *
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testTRTPBasic(): void
    {

        $row    = [0, 1, 2, 3, 4, 5, 6, '/TRTP/SEPA OVERBOEKING/IBAN/NL23ABNA0000000000/BIC/ABNANL2A/NAME/baasd dsdsT CJ/REMI/Nullijn/EREF/NOTPROVIDED', '',
                   ''];
        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals('Nullijn', $result[7]);
        $this->assertEquals('baasd dsdsT CJ', $result[8]);
        $this->assertEquals('NL23ABNA0000000000', $result[9]);
    }

    /**
     * Basic TRTP data with empty description
     *
     * @covers \FireflyIII\Import\Specifics\AbnAmroDescription
     */
    public function testTRTPEmptyDescr(): void
    {

        $row    = [0, 1, 2, 3, 4, 5, 6, '/TRTP/SEPA OVERBOEKING/IBAN/NL23ABNA0000000000/BIC/ABNANL2A/NAME/baasd dsdsT CJ/REMI//EREF/NOTPROVIDED', '', ''];
        $parser = new AbnAmroDescription;
        $result = $parser->run($row);
        $this->assertEquals('SEPA OVERBOEKING -  (NOTPROVIDED)', $result[7]);
        $this->assertEquals('baasd dsdsT CJ', $result[8]);
        $this->assertEquals('NL23ABNA0000000000', $result[9]);
    }


}
