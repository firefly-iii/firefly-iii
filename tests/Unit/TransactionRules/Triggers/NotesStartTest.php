<?php
/**
 * NotesStartTest.php
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
use FireflyIII\TransactionRules\Triggers\NotesStart;
use Tests\TestCase;

/**
 * Class NotesStartTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class NotesStartTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesStart::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(54);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Blabliepblabla';
        $note->save();
        $trigger = NotesStart::makeFromStrings('blaBlie', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesStart::triggered
     */
    public function testTriggeredLonger()
    {
        $journal = TransactionJournal::find(55);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'blabla';
        $note->save();
        $trigger = NotesStart::makeFromStrings('Blablabla', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesStart::triggered
     */
    public function testTriggeredNoMatch()
    {
        $journal = TransactionJournal::find(56);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'blabla';
        $note->save();
        $trigger = NotesStart::makeFromStrings('12345', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesStart::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = NotesStart::willMatchEverything($value);
        $this->assertTrue($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesStart::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = NotesStart::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesStart::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = NotesStart::willMatchEverything($value);
        $this->assertTrue($result);
    }
}