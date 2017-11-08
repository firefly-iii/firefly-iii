<?php
/**
 * HasAttachmentTest.php
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
use FireflyIII\TransactionRules\Triggers\HasAttachment;
use Tests\TestCase;

/**
 * Class HasAttachmentTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class HasAttachmentTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAttachment::triggered
     */
    public function testTriggered()
    {
        $journal    = TransactionJournal::find(26);
        $attachment = $journal->user->attachments()->first();
        $journal->attachments()->save($attachment);
        $this->assertEquals(1, $journal->attachments()->count());

        $trigger = HasAttachment::makeFromStrings('1', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAttachment::triggered
     */
    public function testTriggeredFalse()
    {
        $journal = TransactionJournal::find(27);
        $this->assertEquals(0, $journal->attachments()->count());

        $trigger = HasAttachment::makeFromStrings('1', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAttachment::willMatchEverything
     */
    public function testWillMatchEverything()
    {
        $value  = '5';
        $result = HasAttachment::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAttachment::willMatchEverything
     */
    public function testWillMatchEverythingTrue()
    {
        $value  = -1;
        $result = HasAttachment::willMatchEverything($value);
        $this->assertTrue($result);
    }

}
