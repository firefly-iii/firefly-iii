<?php
/**
 * RabobankDescriptionTest.php
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

namespace tests\Unit\Import\Specifics;


use FireflyIII\Import\Specifics\RabobankDescription;
use Tests\TestCase;

/**
 * Class RabobankDescriptionTest
 */
class RabobankDescriptionTest extends TestCase
{
    /**
     * Default behaviour
     * @covers \FireflyIII\Import\Specifics\RabobankDescription
     */
    public function testRunBasic(): void
    {
        $row = ['','','','','','','','','','',''];

        $parser = new RabobankDescription;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);
    }

    /**
     * No opposite name or iban
     * @covers \FireflyIII\Import\Specifics\RabobankDescription
     */
    public function testRunUseDescription(): void
    {
        $row = ['','','','','','','','','','','Hello'];

        $parser = new RabobankDescription;
        $result = $parser->run($row);
        $this->assertEquals('Hello', $result[6]);
        $this->assertEquals('', $result[10]);
    }

    /**
     * Has opposite name or iban
     * @covers \FireflyIII\Import\Specifics\RabobankDescription
     */
    public function testRunUseFilledIn(): void
    {
        $row = ['','','','','','ABC','','','','',''];

        $parser = new RabobankDescription;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);
    }

}
