<?php
/**
 * AppendNotesTest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Actions;

use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\AppendNotes;
use Log;
use Tests\TestCase;
use DB;
/**
 * Class AppendNotesTest
 */
class AppendNotesTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\AppendNotes
     */
    public function testActOnArray(): void
    {
        // give journal some notes.
        $journal = $this->user()->transactionJournals()->where('description','Rule action note test transaction.')->first();

        // make sure all notes deleted:
        DB::table('notes')->where('noteable_id', $journal->id)->where('noteable_type', TransactionJournal::class)->delete();

        // array for action:
        $array = [
            'transaction_journal_id' => $journal->id
        ];
        $toAppend = 'Text to append to note.';

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $toAppend;
        $action                   = new AppendNotes($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        $newNote = $journal->notes()->first();
        $this->assertEquals($toAppend, $newNote->text);
    }

}
