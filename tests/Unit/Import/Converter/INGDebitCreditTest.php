<?php
/**
 * INGDebitCreditTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Import\Converter;

use FireflyIII\Import\Converter\INGDebitCredit;
use Tests\TestCase;

/**
 * Class INGDebitCreditTest
 */
class INGDebitCreditTest extends TestCase
{
    /**
     * @covers \FireflyIII\Import\Converter\INGDebitCredit::convert()
     */
    public function testConvertAf()
    {
        $converter = new INGDebitCredit;
        $result    = $converter->convert('Af');
        $this->assertEquals(-1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\INGDebitCredit::convert()
     */
    public function testConvertAnything()
    {
        $converter = new INGDebitCredit;
        $result    = $converter->convert('9083jkdkj');
        $this->assertEquals(1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\INGDebitCredit::convert()
     */
    public function testConvertBij()
    {
        $converter = new INGDebitCredit;
        $result    = $converter->convert('Bij');
        $this->assertEquals(1, $result);
    }
}