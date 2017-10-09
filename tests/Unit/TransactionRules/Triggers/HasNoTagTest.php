<?php
/**
 * HasNoTagTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasNoTag;
use Tests\TestCase;

/**
 * Class HasNoTagTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class HasNoTagTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoTag::triggered
     */
    public function testTriggeredNoTag()
    {
        $journal = TransactionJournal::find(34);
        $journal->tags()->detach();
        $this->assertEquals(0, $journal->tags()->count());


        $trigger = HasNoTag::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoTag::triggered
     */
    public function testTriggeredTag()
    {
        $journal = TransactionJournal::find(35);
        $tag     = $journal->user->tags()->first();
        $journal->tags()->detach();
        $journal->tags()->save($tag);
        $this->assertEquals(1, $journal->tags()->count());

        $trigger = HasNoTag::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasNoTag::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = HasNoTag::willMatchEverything($value);
        $this->assertFalse($result);
    }
}