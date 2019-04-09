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

use FireflyIII\Import\Converter\BankDebitCredit;
use FireflyIII\Import\Converter\INGDebitCredit;
use Log;
use Tests\TestCase;

/**
 *
 * Class BankDebitCreditTest
 */
class BankDebitCreditTest extends TestCase
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
     * @covers \FireflyIII\Import\Converter\BankDebitCredit
     */
    public function testConvertA(): void
    {
        $converter = new BankDebitCredit;
        $result    = $converter->convert('A');
        $this->assertEquals(-1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\BankDebitCredit
     */
    public function testConvertAf(): void
    {
        $converter = new BankDebitCredit;
        $result    = $converter->convert('Af');
        $this->assertEquals(-1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\BankDebitCredit
     */
    public function testConvertAnything(): void
    {
        $converter = new BankDebitCredit;
        $result    = $converter->convert('9083jkdkj');
        $this->assertEquals(1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\BankDebitCredit
     */
    public function testConvertBij(): void
    {
        $converter = new BankDebitCredit;
        $result    = $converter->convert('Bij');
        $this->assertEquals(1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\BankDebitCredit
     */
    public function testConvertDebet(): void
    {
        $converter = new BankDebitCredit;
        $result    = $converter->convert('Debet');
        $this->assertEquals(-1, $result);
    }
}
