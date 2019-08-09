<?php
/**
 * AmountControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Budget;


use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Log;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class AmountControllerTest
 */
class AmountControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testAmount(): void
    {
        // mock stuff
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $budget     = $this->getRandomBudget();
        $repository->shouldReceive('updateLimitAmount')->andReturn(new BudgetLimit);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('budgetedPerDay')->andReturn('10');

        $this->mockDefaultSession();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('200');
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = ['amount' => 200, 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [$budget->id]), $data);
        $response->assertStatus(200);
        // assert some reactions:
        $response->assertSee($budget->name);
        $response->assertSee('"amount":"200"');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testAmountLargeDiff(): void
    {
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $budget     = $this->getRandomBudget();

        $repository->shouldReceive('updateLimitAmount')->andReturn(new BudgetLimit);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('budgetedPerDay')->andReturn('10');

        $this->mockDefaultSession();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('200');
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = ['amount' => 20000, 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [$budget->id]), $data);
        $response->assertStatus(200);
        $response->assertSee('Usually you budget about 200 per day.');
        $response->assertSee($budget->name);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testPostUpdateIncome(): void
    {
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('setAvailableBudget');
        $repository->shouldReceive('cleanupBudgets');

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $data = ['amount' => '200', 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.income.post'), $data);
        $response->assertStatus(302);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testUpdateIncome(): void
    {
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('getAvailableBudget')->andReturn('1');
        $repository->shouldReceive('cleanupBudgets');


        $this->be($this->user());
        $response = $this->get(route('budgets.income', ['2017-01-01', '2017-01-31']));
        $response->assertStatus(200);
    }
}
