<?php
/**
 * AmountMoreTest.php
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
use FireflyIII\TransactionRules\Triggers\AmountMore;
use Tests\TestCase;

/**
 * Class AmountMoreTest
 */
class AmountMoreTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore::triggered
     */
    public function testTriggeredExact()
    {
        $journal                     = new TransactionJournal;
        $journal->destination_amount = '12.35';
        $trigger                     = AmountMore::makeFromStrings('12.35', false);
        $result                      = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore::triggered
     */
    public function testTriggeredMore()
    {
        $journal                     = new TransactionJournal;
        $journal->destination_amount = '12.34';
        $trigger                     = AmountMore::makeFromStrings('12.10', false);
        $result                      = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore::triggered
     */
    public function testTriggeredNotMore()
    {
        $journal                     = new TransactionJournal;
        $journal->destination_amount = '12.35';
        $trigger                     = AmountMore::makeFromStrings('12.50', false);
        $result                      = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = '1';
        $result = AmountMore::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = AmountMore::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore::willMatchEverything
     */
    public function testWillMatchEverythingZero()
    {
        $value  = '0';
        $result = AmountMore::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
