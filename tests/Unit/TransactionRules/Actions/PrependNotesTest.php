<?php
/**
 * PrependNotesTest.php
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
use FireflyIII\TransactionRules\Actions\PrependNotes;
use Tests\TestCase;

/**
 * Class PrependNotesTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class PrependNotesTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\PrependNotes::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\PrependNotes::act()
     */
    public function testAct()
    {
        // give journal some notes.
        $journal   = TransactionJournal::find(8);
        $note      = $journal->notes()->first();
        $start     = 'Default note text';
        $toPrepend = 'This is prepended';
        if (is_null($note)) {
            $note = new Note();
            $note->noteable()->associate($journal);
        }
        $note->text = $start;
        $note->save();

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $toPrepend;
        $action                   = new PrependNotes($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        $newNote = $journal->notes()->first();
        $this->assertEquals($toPrepend . $start, $newNote->text);

    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\PrependNotes::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\PrependNotes::act()
     */
    public function testActNewNote()
    {
        // give journal some notes.
        $journal = TransactionJournal::find(4);
        $note    = $journal->notes()->first();
        if (!is_null($note)) {
            $note->forceDelete();
        }
        $toPrepend = 'This is appended';

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $toPrepend;
        $action                   = new PrependNotes($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        $newNote = $journal->notes()->first();
        $this->assertEquals($toPrepend, $newNote->text);

    }
}
