<?php
/**
 * HasAttachment.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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