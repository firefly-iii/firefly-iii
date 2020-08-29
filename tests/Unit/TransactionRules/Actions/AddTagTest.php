<?php
/**
 * AddTagTest.php
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

use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\AddTag;
use Log;
use Tests\TestCase;

/**
 * Class AddTagTest
 */
class AddTagTest extends TestCase
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
     * @covers \FireflyIII\TransactionRules\Actions\AddTag
     */
    public function testActExistingTag(): void
    {
        /** @var Tag $tag */
        $tag = $this->user()->tags()->where('tag', 'RuleActionTag')->first();

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->where('description', 'Rule action test transaction.')->first();

        // make sure journal has no tags:
        $journal->tags()->sync([]);
        $journal->save();

        // add single existing tag:
        $journal->tags()->sync([$tag->id]);

        // assert connection exists.
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => $journal->id]);

        // array with data required:
        $array = [
            'user_id'                => $this->user()->id,
            'transaction_journal_id' => $journal->id,
        ];

        // run the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $tag->tag;
        $action                   = new AddTag($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertFalse($result);

        // assert DB is unchanged.
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => $journal->id]);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Actions\AddTag
     */
    public function testActNewTag(): void
    {
        /** @var Tag $tag */
        $tag = $this->user()->tags()->where('tag', 'RuleActionTag')->first();

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->where('description', 'Rule action test transaction.')->first();

        // make sure journal has no tags:
        $journal->tags()->sync([]);
        $journal->save();

        // array with data required:
        $array = [
            'user_id'                => $this->user()->id,
            'transaction_journal_id' => $journal->id,
        ];

        // run the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $tag->tag;
        $action                   = new AddTag($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        // assert DB is updated! Yay!
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => $journal->id]);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\AddTag
     */
    public function testActNullTag(): void
    {
        $newTagName = sprintf('TestTag-%d', $this->randomInt());

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->where('description', 'Rule action test transaction.')->first();

        // make sure journal has no tags:
        $journal->tags()->sync([]);
        $journal->save();

        // array with data required:
        $array = [
            'user_id'                => $this->user()->id,
            'transaction_journal_id' => $journal->id,
        ];

        // run the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $newTagName;
        $action                   = new AddTag($ruleAction);
        $result                   = $action->actOnArray($array);
        $this->assertTrue($result);

        // find the tag in the DB:
        $this->assertDatabaseHas('tags', ['tag' => $newTagName]);

        $tag = Tag::whereTag($newTagName)->first();

        // assert DB is updated! Yay!
        $this->assertDatabaseHas('tag_transaction_journal', ['tag_id' => $tag->id, 'transaction_journal_id' => $journal->id]);
    }
}
