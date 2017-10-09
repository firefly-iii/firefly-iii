<?php
/**
 * AmountLessTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\AmountLess;
use Tests\TestCase;

/**
 * Class AmountLessTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class AmountLessTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountLess::triggered
     */
    public function testTriggeredLess()
    {
        $journal                     = new TransactionJournal;
        $journal->destination_amount = '12.34';
        $trigger                     = AmountLess::makeFromStrings('12.50', false);
        $result                      = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountLess::triggered
     */
    public function testTriggeredExact()
    {
        $journal                     = new TransactionJournal;
        $journal->destination_amount = '12.35';
        $trigger                     = AmountLess::makeFromStrings('12.35', false);
        $result                      = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountLess::triggered
     */
    public function testTriggeredNotLess()
    {
        $journal                     = new TransactionJournal;
        $journal->destination_amount = '12.35';
        $trigger                     = AmountLess::makeFromStrings('12.00', false);
        $result                      = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountLess::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = AmountLess::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountLess::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = AmountLess::willMatchEverything($value);
        $this->assertTrue($result);
    }


}