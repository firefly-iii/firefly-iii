<?php
/**
 * TagIsTest.php
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
