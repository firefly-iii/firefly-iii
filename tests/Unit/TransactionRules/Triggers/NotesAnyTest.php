<?php
/**
 * NotesAnyTest.php
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
