<?php
/**
 * HasAnyTagTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the 
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasAnyTag;
use Tests\TestCase;

/**
 * Class HasAnyTagTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class HasAnyTagTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyTag::triggered
     */
    public function testTriggered()
    {
        $journal  = TransactionJournal::find(25);
        $tag = $journal->user->tags()->first();
        $journal->tags()->detach();
        $journal->tags()->save($tag);

        $this->assertEquals(1, $journal->tags()->count());
        $trigger = HasAnyTag::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyTag::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(24);
        $journal->tags()->detach();
        $this->assertEquals(0, $journal->tags()->count());
        $trigger = HasAnyTag::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyTag::willMatchEverything
     */
    public function testWillMatchEverything()
    {
        $value  = '';
        $result = HasAnyTag::willMatchEverything($value);
        $this->assertFalse($result);
    }

}