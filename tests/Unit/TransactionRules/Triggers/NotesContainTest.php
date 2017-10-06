<?php
/**
 * NotesContainTest.php
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
use FireflyIII\TransactionRules\Triggers\NotesContain;
use Tests\TestCase;

/**
 * Class NotesContainTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class NotesContainTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesContain::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(43);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Bla bliepbla bla';
        $note->save();
        $trigger = NotesContain::makeFromStrings('blIEp', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesContain::triggered
     */
    public function testTriggeredEmpty()
    {
        $journal = TransactionJournal::find(44);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = '';
        $note->save();
        $trigger = NotesContain::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesContain::triggered
     */
    public function testTriggeredPartial()
    {
        $journal = TransactionJournal::find(45);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Some note';
        $note->save();
        $trigger = NotesContain::makeFromStrings('Some note contains', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesContain::triggered
     */
    public function testTriggeredDifferent()
    {
        $journal = TransactionJournal::find(46);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Some note';
        $note->save();
        $trigger = NotesContain::makeFromStrings('82991911', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesContain::triggered
     */
    public function testTriggeredNone()
    {
        $journal = TransactionJournal::find(47);
        $journal->notes()->delete();
        $trigger = NotesContain::makeFromStrings('Bla bla', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesContain::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = NotesContain::willMatchEverything($value);
        $this->assertTrue($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesContain::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = NotesContain::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesContain::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = NotesContain::willMatchEverything($value);
        $this->assertTrue($result);
    }
}