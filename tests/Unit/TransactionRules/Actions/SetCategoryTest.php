<?php
/**
 * SetCategoryTest.php
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
use FireflyIII\TransactionRules\Actions\SetCategory;
use Tests\TestCase;

/**
 * Class SetCategoryTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class SetCategoryTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetCategory::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetCategory::act()
     */
    public function testAct()
    {
        // get journal, remove all budgets
        $journal  = TransactionJournal::find(13);
        $category = $journal->user->categories()->first();
        $journal->categories()->detach();
        $this->assertEquals(0, $journal->categories()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $category->name;
        $action                   = new SetCategory($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        $this->assertEquals(1, $journal->categories()->count());
        $this->assertEquals($category->name, $journal->categories()->first()->name);
    }
}