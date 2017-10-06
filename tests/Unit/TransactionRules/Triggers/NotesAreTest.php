<?php
/**
 * NotesAreTest.php
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
use FireflyIII\TransactionRules\Triggers\NotesAre;
use Tests\TestCase;

/**
 * Class NotesAreTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class NotesAreTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAre::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(39);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Bla bla bla';
        $note->save();
        $trigger = NotesAre::makeFromStrings('Bla bla bla', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAre::triggered
     */
    public function testTriggeredEmpty()
    {
        $journal = TransactionJournal::find(40);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = '';
        $note->save();
        $trigger = NotesAre::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAre::triggered
     */
    public function testTriggeredDifferent()
    {
        $journal = TransactionJournal::find(41);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Some note';
        $note->save();
        $trigger = NotesAre::makeFromStrings('Not the note', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAre::triggered
     */
    public function testTriggeredNone()
    {
        $journal = TransactionJournal::find(42);
        $journal->notes()->delete();
        $trigger = NotesAre::makeFromStrings('Bla bla', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAre::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = NotesAre::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAre::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = NotesAre::willMatchEverything($value);
        $this->assertTrue($result);
    }
}