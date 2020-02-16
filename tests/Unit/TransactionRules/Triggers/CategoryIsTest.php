<?php
/**
 * CategoryIsTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use FireflyIII\TransactionRules\Triggers\CategoryIs;
use Tests\TestCase;

/**
 * Class CategoryIsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryIsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs
     */
    public function testTriggeredJournal(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        $category   = $withdrawal->user->categories()->first();
        $withdrawal->categories()->detach();
        $withdrawal->categories()->save($category);
        $this->assertEquals(1, $withdrawal->categories()->count());

        $trigger = CategoryIs::makeFromStrings($category->name, false);
        $result  = $trigger->triggered($withdrawal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs
     */
    public function testTriggeredNotJournal(): void
    {
        $withdrawal    = $this->getRandomWithdrawal();
        $category      = $withdrawal->user->categories()->first();
        $otherCategory = $withdrawal->user->categories()->where('id', '!=', $category->id)->first();
        $withdrawal->categories()->detach();
        $withdrawal->categories()->save($category);
        $this->assertEquals(1, $withdrawal->categories()->count());

        $trigger = CategoryIs::makeFromStrings($otherCategory->name, false);
        $result  = $trigger->triggered($withdrawal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs
     */
    public function testTriggeredTransaction(): void
    {
        $withdrawal  = $this->getRandomWithdrawal();
        $transaction = $withdrawal->transactions()->first();
        $category    = $withdrawal->user->categories()->first();

        $withdrawal->categories()->detach();
        $transaction->categories()->detach();
        $transaction->categories()->save($category);
        $this->assertEquals(0, $withdrawal->categories()->count());
        $this->assertEquals(1, $transaction->categories()->count());

        $trigger = CategoryIs::makeFromStrings($category->name, false);
        $result  = $trigger->triggered($withdrawal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = 'x';
        $result = CategoryIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = CategoryIs::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
