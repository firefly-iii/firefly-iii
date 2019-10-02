<?php
/**
 * SnsDescriptionTest.php
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


use FireflyIII\Import\Specifics\SnsDescription;
use Log;
use Tests\TestCase;

/**
 * Class SnsDescriptionTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SnsDescriptionTest extends TestCase
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
     * @covers \FireflyIII\Import\Specifics\SnsDescription
     */
    public function testRunBasic(): void
    {
        $row = ['a', 'b', 'c'];

        $parser = new SnsDescription;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);
    }

    /**
     * @covers \FireflyIII\Import\Specifics\SnsDescription
     */
    public function testRunNoQuotes(): void
    {
        $row = ['a', 'b', 'c', 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 'Some text'];

        $parser = new SnsDescription;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);
        $this->assertEquals('Some text', $result[17]);
    }

    /**
     * @covers \FireflyIII\Import\Specifics\SnsDescription
     */
    public function testRunQuotes(): void
    {
        $row = ['a', 'b', 'c', 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, '\'Some text\''];

        $parser = new SnsDescription;
        $result = $parser->run($row);
        $this->assertEquals('Some text', $result[17]);
    }

}
