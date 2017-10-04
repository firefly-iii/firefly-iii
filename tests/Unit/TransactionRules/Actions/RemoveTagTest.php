<?php
/**
 * RemoveTagTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
        $this->assertEquals($tags->count(), $journal->tags()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $firstTag->tag;
        $action                   = new RemoveTag($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        foreach ($journal->tags()->get() as $tag) {
            $this->assertNotEquals($firstTag->id, $tag->id);
        }
        $this->assertEquals(($tags->count() - 1), $journal->tags()->count());
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