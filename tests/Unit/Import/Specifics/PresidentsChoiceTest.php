<?php
/**
 * PresidentsChoiceTest.php
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


use FireflyIII\Import\Specifics\PresidentsChoice;
use Log;
use Tests\TestCase;

/**
 * Class PresidentsChoiceTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PresidentsChoiceTest extends TestCase
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
     * @covers \FireflyIII\Import\Specifics\PresidentsChoice
     */
    public function testRunAmount(): void
    {
        $row = ['', 'Descr', '12.34', '', ''];

        $parser = new PresidentsChoice;
        $result = $parser->run($row);
        $this->assertEquals('-12.340000000000', $result[3]);
        $this->assertEquals('Descr', $result[2]);

    }

    /**
     * @covers \FireflyIII\Import\Specifics\PresidentsChoice
     */
    public function testRunBasic(): void
    {
        $row = [''];

        $parser = new PresidentsChoice;
        $result = $parser->run($row);
        $this->assertEquals($row, $result);

    }

}
