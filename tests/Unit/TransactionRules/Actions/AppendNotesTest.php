<?php
/**
 * AppendNotesTest.php
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

namespace Tests\Unit\TransactionRules\Actions;

use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\AppendNotes;
use Tests\TestCase;

/**
 * Class AppendNotesTest
 */
class AppendNotesTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\AppendNotes::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\AppendNotes::act()
     */
    public function testAct()
    {
        // give journal some notes.
        $journal  = TransactionJournal::find(3);
        $note     = $journal->notes()->first();
        $start    = 'Default note text';
        $toAppend = 'This is appended';
        if (is_null($note)) {
            $note = new Note();
            $note->noteable()->associate($journal);
        }
        $note->text = $start;
        $note->save();

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $toAppend;
        $action                   = new AppendNotes($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        $newNote = $journal->notes()->first();
        $this->assertEquals($start . $toAppend, $newNote->text);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\AppendNotes::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\AppendNotes::act()
     */
    public function testActNewNote()
    {
        // give journal some notes.
        $journal = TransactionJournal::find(4);
        $note    = $journal->notes()->first();
        if (!is_null($note)) {
            $note->forceDelete();
        }
        $toAppend = 'This is appended';

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $toAppend;
        $action                   = new AppendNotes($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        $newNote = $journal->notes()->first();
        $this->assertEquals($toAppend, $newNote->text);
    }
}
