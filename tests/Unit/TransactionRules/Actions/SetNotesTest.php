<?php
/**
 * SetNotesTest.php
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
use FireflyIII\TransactionRules\Actions\SetNotes;
use Tests\TestCase;

/**
 * Class SetNotesTest
 */
class SetNotesTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetNotes::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetNotes::act()
     */
    public function testAct()
    {
        // give journal a note:
        $journal = TransactionJournal::find(15);
        $note    = $journal->notes()->first();
        if (is_null($note)) {
            $note = new Note;
            $note->noteable()->associate($journal);
        }
        $note->text = 'Hello test note';
        $note->save();
        $this->assertEquals(1, $journal->notes()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'These are new notes ' . rand(1, 1234);
        $action                   = new SetNotes($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // assert result
        $this->assertEquals(1, $journal->notes()->count());
        $this->assertEquals($note->id, $journal->notes()->first()->id);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetNotes::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetNotes::act()
     */
    public function testActNoNotes()
    {
        // give journal a note:
        $journal = TransactionJournal::find(16);
        $journal->notes()->forceDelete();
        $this->assertEquals(0, $journal->notes()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'These are new notes ' . rand(1, 1234);
        $action                   = new SetNotes($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // assert result
        $this->assertEquals(1, $journal->notes()->count());
    }
}
