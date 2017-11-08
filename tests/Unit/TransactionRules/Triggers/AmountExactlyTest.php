<?php
/**
 * AmountExactlyTest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\AmountExactly;
use Tests\TestCase;

/**
 * Class AmountExactlyTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class AmountExactlyTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountExactly::triggered
     */
    public function testTriggeredExact()
    {
        $journal                     = new TransactionJournal;
        $journal->destination_amount = '12.34';
        $trigger                     = AmountExactly::makeFromStrings('12.340', false);
        $result                      = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountExactly::triggered
     */
    public function testTriggeredNotExact()
    {
        $journal                     = new TransactionJournal;
        $journal->destination_amount = '12.35';
        $trigger                     = AmountExactly::makeFromStrings('12.340', false);
        $result                      = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountExactly::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = AmountExactly::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountExactly::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = AmountExactly::willMatchEverything($value);
        $this->assertTrue($result);
    }


}
