<?php
/**
 * ToAccountStartsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\ToAccountStarts;
use Tests\TestCase;

/**
 * Class ToAccountStartsTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class ToAccountStartsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountStarts::triggered
     */
    public function testTriggered()
    {
        $journal     = TransactionJournal::find(66);
        $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        $account     = $transaction->account;

        $trigger = ToAccountStarts::makeFromStrings(substr($account->name,0, -3), false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountStarts::triggered
     */
    public function testTriggeredLonger()
    {
        $journal     = TransactionJournal::find(67);
        $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        $account     = $transaction->account;

        $trigger = ToAccountStarts::makeFromStrings('bla-bla-bla' . $account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountStarts::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(68);

        $trigger = ToAccountStarts::makeFromStrings('some name' . rand(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountStarts::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = ToAccountStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountStarts::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = ToAccountStarts::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountStarts::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = ToAccountStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }


}