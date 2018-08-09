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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\BudgetIs;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class BudgetIsTest
 */
class BudgetIsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs
     */
    public function testTriggeredJournal(): void
    {
        do {
            $journal = TransactionJournal::inRandomOrder()->where('transaction_type_id', 1)->whereNull('deleted_at')->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        $budget = $journal->user->budgets()->first();
        $journal->budgets()->detach();
        $journal->budgets()->save($budget);
        $this->assertEquals(1, $journal->budgets()->count());

        $trigger = BudgetIs::makeFromStrings($budget->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs
     */
    public function testTriggeredNotJournal(): void
    {
        do {
            $journal = TransactionJournal::inRandomOrder()->where('transaction_type_id', 1)->whereNull('deleted_at')->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

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
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs
     */
    public function testTriggeredTransaction(): void
    {
        do {
            $journal = TransactionJournal::inRandomOrder()->where('transaction_type_id', 1)->whereNull('deleted_at')->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        /** @var Collection $transactions */
        $transactions = $journal->transactions()->get();
        $budget       = $journal->user->budgets()->first();

        $journal->budgets()->detach();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $transaction->budgets()->detach();
            $transaction->budgets()->save($budget);
            $this->assertEquals(1, $transaction->budgets()->count());
        }

        $this->assertEquals(0, $journal->budgets()->count());

        $trigger = BudgetIs::makeFromStrings($budget->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = 'x';
        $result = BudgetIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = BudgetIs::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
