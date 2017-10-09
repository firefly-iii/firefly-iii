<?php
/**
 * TagIsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\TagIs;
use Tests\TestCase;

/**
 * Class TagIsTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class TagIsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(57);
        $journal->tags()->detach();
        $tags   = $journal->user->tags()->take(3)->get();
        $search = '';
        foreach ($tags as $index => $tag) {
            $journal->tags()->save($tag);
            if ($index === 1) {
                $search = $tag->tag;
            }
        }
        $this->assertEquals(3, $journal->tags()->count());

        $trigger = TagIs::makeFromStrings($search, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs::triggered
     */
    public function testNotTriggered()
    {
        $journal = TransactionJournal::find(58);
        $journal->tags()->detach();
        $this->assertEquals(0, $journal->tags()->count());

        $trigger = TagIs::makeFromStrings('SomeTag', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = TagIs::willMatchEverything($value);
        $this->assertFalse($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = TagIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TagIs::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = TagIs::willMatchEverything($value);
        $this->assertTrue($result);
    }
}