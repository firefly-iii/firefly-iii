<?php
/**
 * NotesAnyTest.php
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
use FireflyIII\TransactionRules\Triggers\NotesAny;
use Tests\TestCase;

/**
 * Class NotesAnyTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class NotesAnyTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAny::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(36);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Bla bla bla';
        $note->save();
        $trigger = NotesAny::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAny::triggered
     */
    public function testTriggeredEmpty()
    {
        $journal = TransactionJournal::find(37);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = '';
        $note->save();
        $trigger = NotesAny::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAny::triggered
     */
    public function testTriggeredNone()
    {
        $journal = TransactionJournal::find(38);
        $journal->notes()->delete();
        $trigger = NotesAny::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAny::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = NotesAny::willMatchEverything($value);
        $this->assertFalse($result);
    }
}