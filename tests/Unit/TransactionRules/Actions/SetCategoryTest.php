<?php
/**
 * SetCategoryTest.php
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

use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\RuleAction;
use FireflyIII\TransactionRules\Actions\SetCategory;
use Tests\TestCase;

/**
 * Class SetCategoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SetCategoryTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetCategory
     */
    public function testAct(): void
    {
        // get journal, remove all budgets
        $journal  = $this->getRandomWithdrawal();
        $category = $this->getRandomCategory();

        $factory = $this->mock(CategoryFactory::class);
        $factory->shouldReceive('setUser');
        $factory->shouldReceive('findOrCreate')->andReturn($category);

        $journal->categories()->detach();
        $this->assertEquals(0, $journal->categories()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $category->name;
        $action                   = new SetCategory($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        $this->assertEquals(1, $journal->categories()->count());
    }
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetCategory
     */
    public function testActNull(): void
    {
        $factory = $this->mock(CategoryFactory::class);
        $factory->shouldReceive('setUser');
        $factory->shouldReceive('findOrCreate')->andReturnNull();


        // get journal, remove all budgets
        $journal  = $this->getRandomWithdrawal();
        $category = $this->getRandomCategory();
        $journal->categories()->detach();
        $this->assertEquals(0, $journal->categories()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $category->name;
        $action                   = new SetCategory($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);

        $this->assertEquals(0, $journal->categories()->count());
    }
}
