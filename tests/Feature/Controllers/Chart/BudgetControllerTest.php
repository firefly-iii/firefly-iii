<?php
/**
 * BudgetControllerTest.php
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

namespace Tests\Feature\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class BudgetControllerTest
 */
class BudgetControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testBudget(string $range): void
    {
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);

        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon('2015-01-01'))->once();
        $repository->shouldReceive('spentInPeriod')->andReturn('-100');
        $generator->shouldReceive('singleSet')->andReturn([])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.budget', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testBudgetLimit(string $range): void
    {
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);

        $repository->shouldReceive('spentInPeriod')->andReturn('-100');
        $generator->shouldReceive('singleSet')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.budget-limit', [1, 1]));
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Chart\BudgetController
     */
    public function testBudgetLimitWrongLimit(): void
    {
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);

        $this->be($this->user());
        $response = $this->get(route('chart.budget.budget-limit', [1, 8]));
        $response->assertStatus(500);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseAsset(string $range): void
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $generator        = $this->mock(GeneratorInterface::class);
        $collector        = $this->mock(TransactionCollectorInterface::class);
        $transactions     = factory(Transaction::class, 10)->make();
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $collector->shouldReceive('setAllAssetAccounts')->once()->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($transactions);

        $generator->shouldReceive('pieChart')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.expense-asset', [1, 1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseCategory(string $range): void
    {
        $generator  = $this->mock(GeneratorInterface::class);
        $collector  = $this->mock(TransactionCollectorInterface::class);
        $catRepos   = $this->mock(CategoryRepositoryInterface::class);
        $repository = $this->mock(BudgetRepositoryInterface::class);


        $transactions = factory(Transaction::class, 10)->make();
        $categories   = factory(Category::class, 10)->make();

        $collector->shouldReceive('setAllAssetAccounts')->once()->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($transactions);

        $catRepos->shouldReceive('getCategories')->andReturn($categories)->once();

        $generator->shouldReceive('pieChart')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.expense-category', [1, 1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseExpense(string $range): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);

        $transactions = factory(Transaction::class, 10)->make();
        $accounts     = factory(Account::class, 10)->make();

        $collector->shouldReceive('setAllAssetAccounts')->once()->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->once()->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($transactions);

        $accountRepos->shouldReceive('getAccountsByType')->andReturn($accounts)->once();

        $generator->shouldReceive('pieChart')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.expense-expense', [1, 1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpage(string $range): void
    {
        $repository             = $this->mock(BudgetRepositoryInterface::class);
        $generator              = $this->mock(GeneratorInterface::class);
        $collector              = $this->mock(TransactionCollectorInterface::class);
        $budget                 = factory(Budget::class)->make();
        $budgetLimit            = factory(BudgetLimit::class)->make();
        $budgetLimit->budget_id = $budget->id;
        $transaction            = factory(Transaction::class)->make();

        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]))->once();
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('spentInPeriod')->andReturn('-100');

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withoutBudget')->andReturnSelf()->once();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection([$transaction]))->once();

        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpageMultiLimit(string $range): void
    {
        $repository     = $this->mock(BudgetRepositoryInterface::class);
        $generator      = $this->mock(GeneratorInterface::class);
        $collector      = $this->mock(TransactionCollectorInterface::class);
        $budget         = factory(Budget::class)->make();
        $one            = factory(BudgetLimit::class)->make();
        $two            = factory(BudgetLimit::class)->make();
        $one->budget_id = $budget->id;
        $two->budget_id = $budget->id;
        $transaction    = factory(Transaction::class)->make();

        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]))->once();
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn(new Collection([$one, $two]));
        $repository->shouldReceive('spentInPeriod')->andReturn('-100');

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withoutBudget')->andReturnSelf()->once();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection([$transaction]))->once();

        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpageNoLimits(string $range): void
    {
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $generator   = $this->mock(GeneratorInterface::class);
        $collector   = $this->mock(TransactionCollectorInterface::class);
        $budget      = factory(Budget::class)->make();
        $transaction = factory(Transaction::class)->make();

        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn(new Collection);
        $repository->shouldReceive('spentInPeriod')->andReturn('-100');

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withoutBudget')->andReturnSelf()->once();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection([$transaction]))->once();

        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\BudgetController
     */
    public function testPeriod(): void
    {
        $repository             = $this->mock(BudgetRepositoryInterface::class);
        $generator              = $this->mock(GeneratorInterface::class);
        $budget                 = factory(Budget::class)->make();
        $budgetLimit            = factory(BudgetLimit::class)->make();
        $budgetLimit->budget_id = $budget->id;
        $fiscalHelper           = $this->mock(FiscalHelperInterface::class);
        $date                   = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $repository->shouldReceive('getBudgetPeriodReport')->andReturn([])->once();
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.budget.period', [1, '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\BudgetController
     */
    public function testPeriodNoBudget(): void
    {
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $repository->shouldReceive('getNoBudgetPeriodReport')->andReturn([])->once();
        $generator->shouldReceive('singleSet')->once()->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.budget.period.no-budget', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
