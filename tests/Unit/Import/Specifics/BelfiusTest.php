<?php
/**
 * BelfiusTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Import\Specifics;


use FireflyIII\Import\Specifics\Belfius;
use Log;
use Tests\TestCase;

/**
 * Class BelfiusTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BelfiusTest extends TestCase
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
     * @covers \FireflyIII\Import\Specifics\Belfius
     */
    public function testEmptyRow(): void
    {
        $row = [1, 2, 3, 4];

        $parser = new Belfius;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);
    }

    /**
     * Data with recurring transaction.
     *
     * @covers \FireflyIII\Import\Specifics\Belfius
     */
    public function testProcessRecurringTransaction(): void
    {
        $row = [0, 1, 2, 3, 4, 'Tom Jones', 6, 7, 8, 9, 10, 11, 12, 13,
                'DOORLOPENDE OPDRACHT 12345678 NAAR BE01 1234 5678 9012 Tom Jones My Description REF. : 01234567890 VAL. 01-01'];

        $parser = new Belfius;
        $result = $parser->run($row);
        $this->assertEquals('My Description', $result[14]);
    }

    /**
     * Data that cannot be parsed.
     *
     * @covers \FireflyIII\Import\Specifics\Belfius
     */
    public function testProcessUnknown(): void
    {
        $row = [0, 1, 2, 3, 4, 'STORE BRUSSEL n/v', 6, 7, 8, 9, 10, 11, 12, 13,
                'AANKOOP BANCONTACT CONTACTLESS MET KAART NR 01234 5678 9012 3456 - FOO BAR OP 01/01 00:01 STORE BRUSSEL n/v REF. :   01234567890 VAL. 01-01'];

        $parser = new Belfius;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);
    }
}
