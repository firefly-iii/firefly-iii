<?php
/**
 * BudgetIsTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\Transaction;
use FireflyIII\TransactionRules\Triggers\BudgetIs;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class BudgetIsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BudgetIsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs
     */
    public function testTriggeredJournal(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        $budget     = $withdrawal->user->budgets()->first();
        $withdrawal->budgets()->detach();
        $withdrawal->budgets()->save($budget);
        $this->assertEquals(1, $withdrawal->budgets()->count());

        $trigger = BudgetIs::makeFromStrings($budget->name, false);
        $result  = $trigger->triggered($withdrawal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs
     */
    public function testTriggeredNotJournal(): void
    {
        $withdrawal  = $this->getRandomWithdrawal();
        $budget      = $withdrawal->user->budgets()->first();
        $otherBudget = $withdrawal->user->budgets()->where('id', '!=', $budget->id)->first();
        $withdrawal->budgets()->detach();
        $withdrawal->budgets()->save($budget);
        $this->assertEquals(1, $withdrawal->budgets()->count());

        $trigger = BudgetIs::makeFromStrings($otherBudget->name, false);
        $result  = $trigger->triggered($withdrawal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\BudgetIs
     */
    public function testTriggeredTransaction(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        /** @var Collection $transactions */
        $transactions = $withdrawal->transactions()->get();
        $budget       = $withdrawal->user->budgets()->first();

        $withdrawal->budgets()->detach();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $transaction->budgets()->detach();
            $transaction->budgets()->save($budget);
            $this->assertEquals(1, $transaction->budgets()->count());
        }

        $this->assertEquals(0, $withdrawal->budgets()->count());

        $trigger = BudgetIs::makeFromStrings($budget->name, false);
        $result  = $trigger->triggered($withdrawal);
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
