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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasAnyBudget;
use Log;
use Tests\TestCase;

/**
 * Class HasAnyBudgetTest
 */
class HasAnyBudgetTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyBudget
     */
    public function testTriggered(): void
    {
        $loop = 0;
        do {
            /** @var TransactionJournal $journal */
            $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count   = $journal->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        $budget = $journal->user->budgets()->first();
        $journal->budgets()->detach();
        $journal->budgets()->save($budget);

        $this->assertEquals(1, $journal->budgets()->count());
        $trigger = HasAnyBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);

    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyBudget
     */
    public function testTriggeredNot(): void
    {
        $loop = 0;
        do {
            /** @var TransactionJournal $journal */
            $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count   = $journal->transactions()->count();
            $loop++;
        } while ($count !== 2 && $loop < 30);

        $journal->budgets()->detach();
        $this->assertEquals(0, $journal->budgets()->count());

        // also detach all transactions:
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            $transaction->budgets()->detach();
        }

        $trigger = HasAnyBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyBudget
     */
    public function testTriggeredTransactions(): void
    {
        Log::debug('Now in testTriggeredTransactions()');
        $loop = 0;
        do {
            Log::debug(sprintf('Loop is now at #%d', $loop));
            /** @var TransactionJournal $journal */
            $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count   = $journal->transactions()->count();

            Log::debug(sprintf('Found journal #%d with %d transactions', $journal->id, $count));

            $loop++;
        } while ($count !== 2 && $loop < 30);
        Log::debug('end of loop!');

        $budget = $journal->user->budgets()->first();
        Log::debug(sprintf('First budget is %d ("%s")', $budget->id, $budget->name));
        $journal->budgets()->detach();
        $this->assertEquals(0, $journal->budgets()->count());
        Log::debug('Survived the assumption.');

        // append to transaction
        Log::debug('Do transaction loop.');
        foreach ($journal->transactions()->get() as $index => $transaction) {
            Log::debug(sprintf('Now at index #%d, transaction #%d', $index, $transaction->id));
            $transaction->budgets()->detach();
            if (0 === $index) {
                Log::debug('Index is zero, attach budget.');
                $transaction->budgets()->save($budget);
            }
        }
        Log::debug('Done with loop, make trigger');
        $trigger = HasAnyBudget::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyBudget
     */
    public function testWillMatchEverything(): void
    {
        $value  = '';
        $result = HasAnyBudget::willMatchEverything($value);
        $this->assertFalse($result);
    }
}
