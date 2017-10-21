<?php
/**
 * NotesContainTest.php
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