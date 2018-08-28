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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasAttachment;
use Tests\TestCase;

/**
 * Class HasAttachmentTest
 */
class HasAttachmentTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAttachment
     */
    public function testTriggered(): void
    {
        do {
            $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count   = $journal->attachments()->count();
        } while ($count !== 0);

        $attachment = $journal->user->attachments()->first();
        $journal->attachments()->save($attachment);
        $this->assertEquals(1, $journal->attachments()->count());

        $trigger = HasAttachment::makeFromStrings('1', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAttachment
     */
    public function testTriggeredFalse(): void
    {
        do {
            // this is kind of cheating but OK.
            $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count   = $journal->attachments()->count();
        } while ($count !== 0);

        $this->assertEquals(0, $journal->attachments()->count());

        $trigger = HasAttachment::makeFromStrings('1', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAttachment
     */
    public function testWillMatchEverything(): void
    {
        $value  = '5';
        $result = HasAttachment::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAttachment
     */
    public function testWillMatchEverythingTrue(): void
    {
        $value  = -1;
        $result = HasAttachment::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
