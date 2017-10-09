<?php
/**
 * SetNotesTest.php
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
use FireflyIII\TransactionRules\Actions\ClearNotes;
use FireflyIII\TransactionRules\Actions\SetNotes;
use Tests\TestCase;

/**
 * Class SetNotesTest
 *
 * @package Tests\Unit\TransactionRules\Actions
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