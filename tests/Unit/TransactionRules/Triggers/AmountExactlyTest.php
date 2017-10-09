<?php
/**
 * AmountExactlyTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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