<?php
/**
 * HasNoTagTest.php
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