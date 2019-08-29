<?php
/**
 * ExpenseControllerTest.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Report;

use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class ExpenseControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExpenseControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testBudget(): void
    {

        $repository   = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $expense      = $this->getRandomExpense();
        $revenue      = $this->getRandomRevenue();
        $date         = new Carbon;
        $transactions = [$this->getRandomWithdrawalAsArray()];

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');


        // dont care about any calls, just return a default set of fake transactions:
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn($transactions)->atLeast()->once();


        $this->be($this->user());
        $response = $this->get(route('report-data.expense.budget', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testCategory(): void
    {
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $expense      = $this->getRandomExpense();
        $revenue      = $this->getRandomRevenue();
        $date         = new Carbon;
        $one          = $this->getRandomWithdrawalAsArray();
        $two          = $this->getRandomWithdrawalAsArray();

        // two categories
        $oneCat = $this->getRandomCategory();
        $twoCat = $this->user()->categories()->where('id', '!=', $oneCat->id)->inRandomOrder()->first();

        $one['category_id']   = $oneCat->id;
        $one['category_name'] = $oneCat->name;
        $two['category_id']   = $twoCat->id;
        $two['category_name'] = $twoCat->name;

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once();

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$one], [$two])->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('report-data.expense.category', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testSpent(): void
    {
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);

        $expense      = $this->getRandomExpense();
        $revenue      = $this->getRandomRevenue();
        $date         = new Carbon;
        $transactions = [$this->getRandomWithdrawalAsArray()];

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once();

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn($transactions)->atLeast()->once();
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');

        $this->be($this->user());
        $response = $this->get(route('report-data.expense.spent', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testTopExpense(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);

        $expense      = $this->getRandomExpense();
        $revenue      = $this->getRandomRevenue();
        $date         = new Carbon;
        $transactions = [$this->getRandomWithdrawalAsArray()];

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once();

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('100');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->atLeast()->once();

        $collector->shouldReceive('setAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn($transactions)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('report-data.expense.expenses', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testTopIncome(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $repository   = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);

        $expense      = $this->getRandomExpense();
        $revenue      = $this->getRandomRevenue();
        $date         = new Carbon;
        $transactions = [$this->getRandomWithdrawalAsArray()];

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once();

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn($transactions)->atLeast()->once();

        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('100');

        $this->be($this->user());
        $response = $this->get(route('report-data.expense.income', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

}
