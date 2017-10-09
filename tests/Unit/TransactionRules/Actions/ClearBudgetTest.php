<?php
/**
 * ClearBudgetTest.php
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
use FireflyIII\TransactionRules\Actions\ClearBudget;
use Tests\TestCase;

/**
 * Class ClearBudgetTest
 *
 * @package Tests\Unit\TransactionRules\Actions
 */
class ClearBudgetTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\ClearBudget::__construct()
     * @covers \FireflyIII\TransactionRules\Actions\ClearBudget::act()
     */
    public function testAct()
    {
        // associate budget with journal:
        $journal = TransactionJournal::find(5);
        $budget  = $journal->user->budgets()->first();
        $journal->budgets()->save($budget);
        $this->assertGreaterThan(0, $journal->budgets()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = null;
        $action                   = new ClearBudget($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // assert result
        $this->assertEquals(0, $journal->budgets()->count());

    }
}