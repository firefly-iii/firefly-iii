<?php
/**
 * HasNoCategoryTest.php
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
use FireflyIII\TransactionRules\Triggers\HasNoCategory;
use Tests\TestCase;

/**
 * Class HasNoCategoryTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class HasNoCategoryTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoCategory::triggered
     */
    public function testTriggeredCategory()
    {
        $journal  = TransactionJournal::find(31);
        $category = $journal->user->categories()->first();
        $journal->categories()->detach();
        $journal->categories()->save($category);
        $this->assertEquals(1, $journal->categories()->count());

        $trigger = HasNoCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoCategory::triggered
     */
    public function testTriggeredNoCategory()
    {
        $journal = TransactionJournal::find(32);
        $journal->categories()->detach();
        $this->assertEquals(0, $journal->categories()->count());


        $trigger = HasNoCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoCategory::triggered
     */
    public function testTriggeredTransaction()
    {
        $journal     = TransactionJournal::find(33);
        $transaction = $journal->transactions()->first();
        $category    = $journal->user->categories()->first();

        $journal->categories()->detach();
        $transaction->categories()->save($category);
        $this->assertEquals(0, $journal->categories()->count());
        $this->assertEquals(1, $transaction->categories()->count());


        $trigger = HasNoCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoCategory::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = HasNoCategory::willMatchEverything($value);
        $this->assertFalse($result);
    }
}