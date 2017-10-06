<?php
/**
 * CategoryIsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\CategoryIs;
use Tests\TestCase;

/**
 * Class CategoryIsTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class CategoryIsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs::triggered
     */
    public function testTriggeredJournal()
    {
        $journal  = TransactionJournal::find(17);
        $category = $journal->user->categories()->first();
        $journal->categories()->detach();
        $journal->categories()->save($category);
        $this->assertEquals(1, $journal->categories()->count());

        $trigger = CategoryIs::makeFromStrings($category->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs::triggered
     */
    public function testTriggeredNotJournal()
    {
        $journal       = TransactionJournal::find(18);
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
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs::triggered
     */
    public function testTriggeredTransaction()
    {
        $journal     = TransactionJournal::find(19);
        $transaction = $journal->transactions()->first();
        $category    = $journal->user->categories()->first();

        $journal->categories()->detach();
        $transaction->categories()->save($category);
        $this->assertEquals(0, $journal->categories()->count());
        $this->assertEquals(1, $transaction->categories()->count());


        $trigger = CategoryIs::makeFromStrings($category->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = CategoryIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\CategoryIs::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = CategoryIs::willMatchEverything($value);
        $this->assertTrue($result);
    }
}