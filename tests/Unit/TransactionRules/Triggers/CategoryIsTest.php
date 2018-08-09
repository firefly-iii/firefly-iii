<?php
/**
 * CategoryIsTest.php
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

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\CategoryIs;
use Tests\TestCase;

/**
 * Class CategoryIsTest
 */
class CategoryIsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs
     */
    public function testTriggeredJournal(): void
    {
        do {
            $journal      = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $transactions = $journal->transactions()->count();
        } while ($transactions !== 2);

        $category = $journal->user->categories()->first();
        $journal->categories()->detach();
        $journal->categories()->save($category);
        $this->assertEquals(1, $journal->categories()->count());

        $trigger = CategoryIs::makeFromStrings($category->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs
     */
    public function testTriggeredNotJournal(): void
    {
        do {
            $journal      = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $transactions = $journal->transactions()->count();
        } while ($transactions !== 2);

        $category      = $journal->user->categories()->first();
        $otherCategory = $journal->user->categories()->where('id', '!=', $category->id)->first();
        $journal->categories()->detach();
        $journal->categories()->save($category);
        $this->assertEquals(1, $journal->categories()->count());

        $trigger = CategoryIs::makeFromStrings($otherCategory->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs
     */
    public function testTriggeredTransaction(): void
    {
        do {
            $journal      = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $transactions = $journal->transactions()->count();
        } while ($transactions !== 2);

        $transaction = $journal->transactions()->first();
        $category    = $journal->user->categories()->first();

        $journal->categories()->detach();
        $transaction->categories()->detach();
        $transaction->categories()->save($category);
        $this->assertEquals(0, $journal->categories()->count());
        $this->assertEquals(1, $transaction->categories()->count());

        $trigger = CategoryIs::makeFromStrings($category->name, false);
        $result  = $trigger->triggered($journal);
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
