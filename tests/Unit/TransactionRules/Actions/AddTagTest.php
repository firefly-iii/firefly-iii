<?php
/**
 * AddTagTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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