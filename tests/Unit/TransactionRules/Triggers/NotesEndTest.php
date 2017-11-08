<?php
/**
 * NotesEndTest.php
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
use FireflyIII\TransactionRules\Triggers\NotesEnd;
use Tests\TestCase;

/**
 * Class NotesEndTest
 *
 * @package Unit\TransactionRules\Triggers
 */
class NotesEndTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEnd::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(51);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Bla bliepblabla';
        $note->save();
        $trigger = NotesEnd::makeFromStrings('blaBla', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEnd::triggered
     */
    public function testTriggeredLonger()
    {
        $journal = TransactionJournal::find(53);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'blabla';
        $note->save();
        $trigger = NotesEnd::makeFromStrings('Blablabla', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEnd::triggered
     */
    public function testTriggeredNoMatch()
    {
        $journal = TransactionJournal::find(52);
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'blabla';
        $note->save();
        $trigger = NotesEnd::makeFromStrings('12345', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEnd::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = NotesEnd::willMatchEverything($value);
        $this->assertTrue($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEnd::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = NotesEnd::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesEnd::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = NotesEnd::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
