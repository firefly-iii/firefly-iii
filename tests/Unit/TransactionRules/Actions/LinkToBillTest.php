<?php
/**
 * LinkToBillTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\TransactionRules\Actions\LinkToBill;
use Log;
use Tests\TestCase;

/**
 * Class LinkToBillTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LinkToBillTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\TransactionRules\Actions\LinkToBill
     */
    public function testBasic(): void
    {
        $repos                    = $this->mock(BillRepositoryInterface::class);
        $withdrawal               = $this->getRandomWithdrawal();
        $rule                     = $this->getRandomRule();
        $bill                     = $this->getRandomBill();
        $ruleAction               = new RuleAction;
        $ruleAction->rule         = $rule;
        $ruleAction->action_type  = 'link_to_bill';
        $ruleAction->action_value = $bill->name;

        $repos->shouldReceive('setUser');
        $repos->shouldReceive('findByName')->withArgs([$bill->name])->andReturn($bill);

        $action = new LinkToBill($ruleAction);
        $result = $action->act($withdrawal);

        $this->assertTrue($result);


    }


    /**
     * @covers \FireflyIII\TransactionRules\Actions\LinkToBill
     */
    public function testNoBill(): void
    {
        $repos                    = $this->mock(BillRepositoryInterface::class);
        $withdrawal               = $this->getRandomWithdrawal();
        $rule                     = $this->getRandomRule();
        $bill                     = $this->getRandomBill();
        $ruleAction               = new RuleAction;
        $ruleAction->rule         = $rule;
        $ruleAction->action_type  = 'link_to_bill';
        $ruleAction->action_value = $bill->name;

        $repos->shouldReceive('setUser');
        $repos->shouldReceive('findByName')->withArgs([$bill->name])->andReturnNull();

        $action = new LinkToBill($ruleAction);
        $result = $action->act($withdrawal);

        $this->assertFalse($result);


    }
}
