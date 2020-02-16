<?php
/**
 * RemoveTagTest.php
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

use DB;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\RemoveTag;
use Tests\TestCase;

/**
 * Class RemoveTagTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RemoveTagTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\RemoveTag
     */
    public function testAct(): void
    {

        // find journal with at least one tag
        $journalIds = DB::table('tag_transaction_journal')->get(['transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
        $journalId  = (int)$journalIds[0];
        /** @var TransactionJournal $journal */
        $journal       = TransactionJournal::find($journalId);
        $originalCount = $journal->tags()->count();
        $firstTag      = $journal->tags()->first();

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $firstTag->tag;
        $action                   = new RemoveTag($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        foreach ($journal->tags()->get() as $tag) {
            $this->assertNotEquals($firstTag->id, $tag->id);
        }
        $this->assertEquals($originalCount - 1, $journal->tags()->count());
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\RemoveTag
     */
    public function testActNoTag(): void
    {
        // get journal, link al tags:
        /** @var TransactionJournal $journal */
        $journal = $this->getRandomWithdrawal();
        $tags    = $journal->user->tags()->get();
        $journal->tags()->sync($tags->pluck('id')->toArray());
        $this->assertEquals($tags->count(), $journal->tags()->get()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $this->randomInt() . 'nosuchtag';
        $action                   = new RemoveTag($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        $this->assertEquals($tags->count(), $journal->tags()->count());
    }
}
