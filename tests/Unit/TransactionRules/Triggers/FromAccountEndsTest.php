<?php
/**
 * FromAccountEndsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\FromAccountEnds;
use Tests\TestCase;

/**
 * Class FromAccountEndsTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class FromAccountEndsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::triggered
     */
    public function testTriggered()
    {
        $journal     = TransactionJournal::find(22);
        $transaction = $journal->transactions()->where('amount', '<', 0)->first();
        $account     = $transaction->account;

        $trigger = FromAccountEnds::makeFromStrings(substr($account->name, -3), false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::triggered
     */
    public function testTriggeredLonger()
    {
        $journal     = TransactionJournal::find(22);
        $transaction = $journal->transactions()->where('amount', '<', 0)->first();
        $account     = $transaction->account;

        $trigger = FromAccountEnds::makeFromStrings('bla-bla-bla' . $account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(23);

        $trigger = FromAccountEnds::makeFromStrings('some name' . rand(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = FromAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = FromAccountEnds::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = FromAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }


}