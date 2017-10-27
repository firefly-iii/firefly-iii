<?php
/**
 * HasAnyBudgetTest.php
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