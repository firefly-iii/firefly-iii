<?php
/**
 * RemoveAllTagsTest.php
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
