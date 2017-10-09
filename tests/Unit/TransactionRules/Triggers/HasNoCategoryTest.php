<?php
/**
 * HasNoCategoryTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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