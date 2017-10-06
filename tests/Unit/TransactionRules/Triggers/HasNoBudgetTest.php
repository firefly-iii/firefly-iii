<?php
/**
 * HasNoBudgetTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasNoBudget;
use Tests\TestCase;

/**
 * Class HasNoBudgetTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class HasNoBudgetTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoBudget::triggered
     */
    public function testTriggeredBudget()
    {
        $journal = TransactionJournal::find(28);
        $budget  = $journal->user->budgets()->first();
        $journal->budgets()->detach();
        $journal->budgets()->save($budget);
        $this->assertEquals(1, $journal->budgets()->count());

        $trigger = HasNoBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoBudget::triggered
     */
    public function testTriggeredNoBudget()
    {
        $journal = TransactionJournal::find(29);
        $journal->budgets()->detach();
        $this->assertEquals(0, $journal->budgets()->count());


        $trigger = HasNoBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoBudget::triggered
     */
    public function testTriggeredTransaction()
    {
        $journal     = TransactionJournal::find(30);
        $transaction = $journal->transactions()->first();
        $budget      = $journal->user->budgets()->first();

        $journal->budgets()->detach();
        $transaction->budgets()->save($budget);
        $this->assertEquals(0, $journal->budgets()->count());
        $this->assertEquals(1, $transaction->budgets()->count());


        $trigger = HasNoBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoBudget::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = HasNoBudget::willMatchEverything($value);
        $this->assertFalse($result);
    }
}