<?php
/**
 * HasAnyCategoryTest.php
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
use FireflyIII\TransactionRules\Triggers\HasAnyCategory;
use Tests\TestCase;

/**
 * Class HasAnyCategoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class HasAnyCategoryTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyCategory
     */
    public function testTriggered(): void
    {
        $journal  = $this->getRandomWithdrawal();
        $category = $this->getRandomCategory();;
        $journal->categories()->detach();
        $journal->categories()->save($category);

        $this->assertEquals(1, $journal->categories()->count());
        $trigger = HasAnyCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyCategory
     */
    public function testTriggeredNot(): void
    {
        $journal = $this->getRandomWithdrawal();
        $journal->categories()->detach();

        // also detach transactions:
        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $transaction->categories()->detach();
        }

        $this->assertEquals(0, $journal->categories()->count());
        $trigger = HasAnyCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyCategory
     */
    public function testTriggeredTransactions(): void
    {
        $withdrawal = $this->getRandomWithdrawal();

        $category = $withdrawal->user->categories()->first();
        $withdrawal->categories()->detach();
        $this->assertEquals(0, $withdrawal->categories()->count());

        // append to transaction, not to journal.
        foreach ($withdrawal->transactions()->get() as $index => $transaction) {
            $transaction->categories()->sync([$category->id]);
            $this->assertEquals(1, $transaction->categories()->count());
        }
        $this->assertEquals(0, $withdrawal->categories()->count());

        $trigger = HasAnyCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($withdrawal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyCategory
     */
    public function testWillMatchEverything(): void
    {
        $value  = '';
        $result = HasAnyCategory::willMatchEverything($value);
        $this->assertFalse($result);
    }
}
