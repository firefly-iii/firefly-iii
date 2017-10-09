<?php
/**
 * AppendNotesTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
 *
 * @package Tests\Unit\TransactionRules\Actions
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