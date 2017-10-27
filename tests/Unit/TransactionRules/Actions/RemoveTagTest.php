<?php
/**
 * RemoveTagTest.php
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
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\RemoveTag;
use Tests\TestCase;

/**
 * Class RemoveTagTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class RemoveTagTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\RemoveTag::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\RemoveTag::act()
     */
    public function testAct()
    {
        // get journal, link al tags:
        $journal = TransactionJournal::find(10);
        $tags    = $journal->user->tags()->get();
        foreach ($tags as $tag) {
            $journal->tags()->save($tag);
            $journal->save();
        }
        $firstTag = $tags->first();
        $oldCount = $journal->tags()->count();
        $this->assertGreaterThan(0, $journal->tags()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $firstTag->tag;
        $action                   = new RemoveTag($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        foreach ($journal->tags()->get() as $tag) {
            $this->assertNotEquals($firstTag->id, $tag->id);
        }
        $this->assertEquals(($oldCount - 1), $journal->tags()->count());
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\RemoveTag::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\RemoveTag::act()
     */
    public function testActNoTag()
    {
        // get journal, link al tags:
        $journal = TransactionJournal::find(11);
        $tags    = $journal->user->tags()->get();
        foreach ($tags as $tag) {
            $journal->tags()->save($tag);
            $journal->save();
        }
        $this->assertEquals($tags->count(), $journal->tags()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = rand(1, 1234) . 'nosuchtag';
        $action                   = new RemoveTag($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        $this->assertEquals($tags->count(), $journal->tags()->count());
    }
}