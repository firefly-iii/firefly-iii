<?php
/**
 * BankDebitCreditTest.php
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

namespace Tests\Unit\Import\Converter;

use FireflyIII\Import\Converter\BankDebitCredit;
use FireflyIII\Import\Converter\INGDebitCredit;
use Log;
use Tests\TestCase;

/**
 *
 * Class BankDebitCreditTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
