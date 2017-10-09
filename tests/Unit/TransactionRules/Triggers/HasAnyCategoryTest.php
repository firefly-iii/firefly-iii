<?php
/**
 * HasAnyCategoryTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasAnyCategory;
use Tests\TestCase;

/**
 * Class HasAnyCategoryTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class HasAnyCategoryTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyCategory::triggered
     */
    public function testTriggered()
    {
        $journal  = TransactionJournal::find(25);
        $category = $journal->user->categories()->first();
        $journal->categories()->detach();
        $journal->categories()->save($category);

        $this->assertEquals(1, $journal->categories()->count());
        $trigger = HasAnyCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyCategory::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(24);
        $journal->categories()->detach();
        $this->assertEquals(0, $journal->categories()->count());
        $trigger = HasAnyCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyCategory::triggered
     */
    public function testTriggeredTransactions()
    {
        $journal  = TransactionJournal::find(26);
        $category = $journal->user->categories()->first();
        $journal->categories()->detach();
        $this->assertEquals(0, $journal->categories()->count());

        // append to transaction
        foreach ($journal->transactions()->get() as $index => $transaction) {
            $transaction->categories()->detach();
            if ($index === 0) {
                $transaction->categories()->save($category);
            }
        }

        $trigger = HasAnyCategory::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyCategory::willMatchEverything
     */
    public function testWillMatchEverything()
    {
        $value  = '';
        $result = HasAnyCategory::willMatchEverything($value);
        $this->assertFalse($result);
    }

}