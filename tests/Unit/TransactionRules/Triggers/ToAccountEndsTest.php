<?php
/**
 * ToAccountEndsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\ToAccountEnds;
use Tests\TestCase;

/**
 * Class ToAccountEndsTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class ToAccountEndsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::triggered
     */
    public function testTriggered()
    {
        $journal     = TransactionJournal::find(61);
        $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        $account     = $transaction->account;

        $trigger = ToAccountEnds::makeFromStrings(substr($account->name, -3), false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::triggered
     */
    public function testTriggeredLonger()
    {
        $journal     = TransactionJournal::find(62);
        $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        $account     = $transaction->account;

        $trigger = ToAccountEnds::makeFromStrings('bla-bla-bla' . $account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(63);

        $trigger = ToAccountEnds::makeFromStrings(strval(rand(1, 234)), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = ToAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = ToAccountEnds::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = ToAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }


}