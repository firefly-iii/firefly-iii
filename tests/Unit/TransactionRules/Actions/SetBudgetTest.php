<?php
/**
 * SetBudgetTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\TransactionRules\Actions\SetBudget;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class SetBudgetTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SetBudgetTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetBudget
     */
    public function testAct(): void
    {
        // get journal, remove all budgets
        $journal     = $this->getRandomWithdrawal();
        $budget      = $this->getRandomBudget();

        $journal->budgets()->sync([]);
        $this->assertEquals(0, $journal->budgets()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $budget->name;
        $action                   = new SetBudget($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        $this->assertEquals(1, $journal->budgets()->count());
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetBudget
     */
    public function testActNull(): void
    {
        // get journal, remove all budgets
        $journal     = $this->getRandomWithdrawal();

        $journal->budgets()->sync([]);
        $this->assertEquals(0, $journal->budgets()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'non-existing budget #' . $this->randomInt();
        $action                   = new SetBudget($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
        $this->assertEquals(0, $journal->budgets()->count());
    }


    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetBudget
     */
    public function testActDeposit(): void
    {
        // get journal, remove all budgets
        $journal     = $this->getRandomDeposit();
        $budget      = $this->getRandomBudget();

        $journal->budgets()->detach();
        $this->assertEquals(0, $journal->budgets()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $budget->name;
        $action                   = new SetBudget($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        $this->assertEquals(0, $journal->budgets()->count());
    }
}
