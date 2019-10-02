<?php
/**
 * HasNoBudgetTest.php
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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasNoBudget;
use Tests\TestCase;

/**
 * Class HasNoBudgetTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class HasNoBudgetTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoBudget
     */
    public function testTriggeredBudget(): void
    {
        $journal = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 1)->whereNull('deleted_at')->first();
        $budget  = $this->getRandomBudget();
        $journal->budgets()->detach();
        $journal->budgets()->save($budget);
        $this->assertEquals(1, $journal->budgets()->count());

        $trigger = HasNoBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoBudget
     */
    public function testTriggeredNoBudget(): void
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 1)->whereNull('deleted_at')->first();
        $journal->budgets()->detach();
        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $transaction->budgets()->detach();
        }
        $this->assertEquals(0, $journal->budgets()->count());

        $trigger = HasNoBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoBudget
     */
    public function testTriggeredTransaction(): void
    {
        $withdrawal = $this->getRandomWithdrawal();

        $transactions = $withdrawal->transactions()->get();
        $budget       = $withdrawal->user->budgets()->first();

        $withdrawal->budgets()->detach();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $transaction->budgets()->sync([$budget->id]);
            $this->assertEquals(1, $transaction->budgets()->count());
        }
        $this->assertEquals(0, $withdrawal->budgets()->count());

        $trigger = HasNoBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($withdrawal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoBudget
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = HasNoBudget::willMatchEverything($value);
        $this->assertFalse($result);
    }
}
