<?php
/**
 * ClearCategoryTest.php
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
use FireflyIII\TransactionRules\Actions\ClearCategory;
use Tests\TestCase;

/**
 * Class ClearCategoryTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class ClearCategoryTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\ClearCategory::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\ClearCategory::act()
     */
    public function testAct()
    {
        // associate budget with journal:
        $journal = TransactionJournal::find(5);
        $category = $journal->user->categories()->first();
        $journal->budgets()->save($category);
        $this->assertGreaterThan(0, $journal->categories()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = null;
        $action                   = new ClearCategory($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // assert result
        $this->assertEquals(0, $journal->categories()->count());

    }
}