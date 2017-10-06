<?php
/**
 * ToAccountContainsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\ToAccountContains;
use Tests\TestCase;

/**
 * Class ToAccountContainsTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class ToAccountContainsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountContains::triggered
     */
    public function testTriggered()
    {
        $journal     = TransactionJournal::find(59);
        $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        $account     = $transaction->account;
        $trigger     = ToAccountContains::makeFromStrings($account->name, false);
        $result      = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountContains::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(60);
        $trigger = ToAccountContains::makeFromStrings('some name' . rand(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountContains::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = ToAccountContains::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountContains::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = ToAccountContains::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountContains::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = ToAccountContains::willMatchEverything($value);
        $this->assertTrue($result);
    }


}