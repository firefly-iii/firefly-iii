<?php
/**
 * BudgetIsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\BudgetIs;
use Tests\TestCase;

/**
 * Class BudgetIsTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class BudgetIsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs::triggered
     */
    public function testTriggeredJournal()
    {
        $journal = TransactionJournal::find(17);
        $budget  = $journal->user->budgets()->first();
        $journal->budgets()->detach();
        $journal->budgets()->save($budget);
        $this->assertEquals(1, $journal->budgets()->count());

        $trigger = BudgetIs::makeFromStrings($budget->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs::triggered
     */
    public function testTriggeredNotJournal()
    {
        $journal     = TransactionJournal::find(18);
        $budget      = $journal->user->budgets()->first();
        $otherBudget = $journal->user->budgets()->where('id', '!=', $budget->id)->first();
        $journal->budgets()->detach();
        $journal->budgets()->save($budget);
        $this->assertEquals(1, $journal->budgets()->count());


        $trigger = BudgetIs::makeFromStrings($otherBudget->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs::triggered
     */
    public function testTriggeredTransaction()
    {
        $journal     = TransactionJournal::find(19);
        $transaction = $journal->transactions()->first();
        $budget      = $journal->user->budgets()->first();

        $journal->budgets()->detach();
        $transaction->budgets()->save($budget);
        $this->assertEquals(0, $journal->budgets()->count());
        $this->assertEquals(1, $transaction->budgets()->count());


        $trigger = BudgetIs::makeFromStrings($budget->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = BudgetIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = BudgetIs::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
