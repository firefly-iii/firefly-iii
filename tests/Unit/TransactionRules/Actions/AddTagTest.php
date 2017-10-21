<?php
/**
 * AddTagTest.php
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


use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\AddTag;
use Tests\TestCase;

class AddTagTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\AddTag::__construct
     * @covers \FireflyIII\TransactionRules\Actions\AddTag::act()
     */
    public function testActExistingTag()
    {
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => 2, 'transaction_journal_id' => 1]);
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'housing';
        $journal                  = TransactionJournal::find(1);
        $action                   = new AddTag($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => 2, 'transaction_journal_id' => 1]);

    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\AddTag::act()
     */
    public function testActNoTag()
    {
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'TestTag-' . rand(1, 1000);
        $journal                  = TransactionJournal::find(1);
        $action                   = new AddTag($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // find newly created tag:
        $tag = Tag::orderBy('id', 'DESC')->first();
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => 1]);

    }

}