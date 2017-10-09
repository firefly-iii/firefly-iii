<?php
/**
 * HasAnyBudgetTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasAnyBudget;
use Tests\TestCase;

/**
 * Class HasAnyBudgetTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class HasAnyBudgetTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyBudget::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(25);
        $budget  = $journal->user->budgets()->first();
        $journal->budgets()->detach();
        $journal->budgets()->save($budget);

        $this->assertEquals(1, $journal->budgets()->count());
        $trigger = HasAnyBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyBudget::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(24);
        $journal->budgets()->detach();
        $this->assertEquals(0, $journal->budgets()->count());
        $trigger = HasAnyBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyBudget::triggered
     */
    public function testTriggeredTransactions()
    {
        $journal = TransactionJournal::find(26);
        $budget  = $journal->user->budgets()->first();
        $journal->budgets()->detach();
        $this->assertEquals(0, $journal->budgets()->count());

        // append to transaction
        foreach ($journal->transactions()->get() as $index => $transaction) {
            $transaction->budgets()->detach();
            if ($index === 0) {
                $transaction->budgets()->save($budget);
            }
        }

        $trigger = HasAnyBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyBudget::willMatchEverything
     */
    public function testWillMatchEverything()
    {
        $value  = '';
        $result = HasAnyBudget::willMatchEverything($value);
        $this->assertFalse($result);
    }

}