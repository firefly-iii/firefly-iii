<?php
/**
 * RemoveAllTagsTest.php
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
use FireflyIII\TransactionRules\Actions\RemoveAllTags;
use Tests\TestCase;

/**
 * Class RemoveAllTagsTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class RemoveAllTagsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\RemoveAllTags::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\RemoveAllTags::act()
     */
    public function testAct()
    {
        // get journal, link al tags:
        $journal = TransactionJournal::find(9);
        $tags    = $journal->user->tags()->get();
        foreach ($tags as $tag) {
            $journal->tags()->save($tag);
            $journal->save();
        }
        $this->assertGreaterThan(0, $journal->tags()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = null;
        $action                   = new RemoveAllTags($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        $this->assertEquals(0, $journal->tags()->count());
    }
}