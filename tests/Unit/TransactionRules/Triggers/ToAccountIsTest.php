<?php
/**
 * ToAccountIsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\ToAccountIs;
use Tests\TestCase;

/**
 * Class ToAccountIsTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class ToAccountIsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountIs::triggered
     */
    public function testTriggered()
    {
        $journal     = TransactionJournal::find(64);
        $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        $account     = $transaction->account;

        $trigger = ToAccountIs::makeFromStrings($account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountIs::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(65);

        $trigger = ToAccountIs::makeFromStrings('some name' . rand(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountIs::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = ToAccountIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountIs::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = ToAccountIs::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountIs::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = ToAccountIs::willMatchEverything($value);
        $this->assertTrue($result);
    }


}