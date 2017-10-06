<?php
/**
 * AmountMoreTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\AmountMore;
use Tests\TestCase;

/**
 * Class AmountMoreTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
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