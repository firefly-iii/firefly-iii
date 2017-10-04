<?php
/**
 * SetBudgetTest.php
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
use FireflyIII\TransactionRules\Actions\SetBudget;
use Tests\TestCase;

/**
 * Class SetBudgetTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class SetBudgetTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetBudget::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\SetBudget::act()
     */
    public function testAct()
    {
        // get journal, remove all budgets
        $journal = TransactionJournal::find(12);
        $budget  = $journal->user->budgets()->first();
        $journal->budgets()->detach();
        $this->assertEquals(0, $journal->budgets()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $budget->name;
        $action                   = new SetBudget($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        $this->assertEquals(1, $journal->budgets()->count());
        $this->assertEquals($budget->name, $journal->budgets()->first()->name);
    }
}