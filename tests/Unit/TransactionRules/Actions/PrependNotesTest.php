<?php
/**
 * PrependNotesTest.php
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