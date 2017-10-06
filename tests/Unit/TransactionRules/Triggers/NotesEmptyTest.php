<?php
/**
 * NotesEmptyTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\NotesEmpty;
use Tests\TestCase;

/**
 * Class NotesEmptyTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class NotesEmptyTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEmpty::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(48);
        $journal->notes()->delete();
        $trigger = NotesEmpty::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEmpty::triggered
     */
    public function testTriggeredEmpty()
    {
        $journal = TransactionJournal::find(49);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = '';
        $note->save();
        $trigger = NotesEmpty::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEmpty::triggered
     */
    public function testTriggeredPartial()
    {
        $journal = TransactionJournal::find(50);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Some note';
        $note->save();
        $trigger = NotesEmpty::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEmpty::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = NotesEmpty::willMatchEverything($value);
        $this->assertFalse($result);
    }

}