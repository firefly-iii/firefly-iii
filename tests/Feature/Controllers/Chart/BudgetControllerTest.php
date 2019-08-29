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
use Exception;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class BudgetControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BudgetControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testBudget(string $range): void
    {
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);
        try {
            $date = new Carbon('2015-01-01');
        } catch (Exception $e) {
            $e->getMessage();
        }

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $repository->shouldReceive('firstUseDate')->andReturn($date)->once();
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

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $repository->shouldReceive('spentInPeriod')->andReturn('-100');
        $generator->shouldReceive('singleSet')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.budget.budget-limit', [1, 1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\BudgetController
     */
    public function testBudgetLimitWrongLimit(): void
    {
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(GeneratorInterface::class);
        $budget = $this->getRandomBudget();
        $limit  = BudgetLimit::where('budget_id', '!=', $budget->id)->first();

        // mock default session
        $this->mockDefaultSession();

        Log::warning('The following error is part of a test.');
        $this->be($this->user());
        $response = $this->get(route('chart.budget.budget-limit', [$budget->id, $limit->id]));
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
        $this->mock(BudgetRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $withdrawal   = $this->getRandomWithdrawalAsArray();
        $destination  = $this->user()->accounts()->find($withdrawal['destination_account_id']);

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection([$destination]))->atLeast()->once();
        $collector->shouldReceive('setBudget')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$withdrawal])->atLeast()->once();

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
        $this->mock(BudgetRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $catRepos   = $this->mock(CategoryRepositoryInterface::class);
        $withdrawal = $this->getRandomWithdrawalAsArray();
        $category   = $this->user()->categories()->find($withdrawal['category_id']);

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $collector->shouldReceive('setBudget')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$withdrawal])->atLeast()->once();

        $catRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]))->once();

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
        $this->mock(BudgetRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $withdrawal   = $this->getRandomWithdrawalAsArray();
        $destination  = $this->user()->accounts()->find($withdrawal['destination_account_id']);

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');


        $collector->shouldReceive('withAccountInformation')->once()->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->once()->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$withdrawal])->atLeast()->once();

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection([$destination]))->once();

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
    public function testFrontPage(string $range): void
    {
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $generator   = $this->mock(GeneratorInterface::class);
        $collector   = $this->mock(GroupCollectorInterface::class);
        $budget      = $this->getRandomBudget();
        $budgetLimit = $this->getRandomBudgetLimit();

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');


        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]))->once();
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('spentInPeriod')->andReturn('-100');

        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withoutBudget')->andReturnSelf()->once();
        $collector->shouldReceive('getSum')->andReturn('-100')->atLeast()->once();

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

        $repository = $this->mock(BudgetRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $budget     = $this->getRandomBudget();
        $limit1     = $this->getRandomBudgetLimit();
        $limit2     = $this->getRandomBudgetLimit();

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]))->once();
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn(new Collection([$limit1, $limit2]));
        $repository->shouldReceive('spentInPeriod')->andReturn('-100')->atLeast()->once();

        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withoutBudget')->andReturnSelf()->once();
        $collector->shouldReceive('getSum')->andReturn('-100')->atLeast()->once();

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

        $repository = $this->mock(BudgetRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $budget     = $this->getRandomBudget();

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn(new Collection);
        $repository->shouldReceive('spentInPeriod')->andReturn('-100');

        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withoutBudget')->andReturnSelf()->once();
        //$collector->shouldReceive('getTransactions')->andReturn([$withdrawal])->once();
        $collector->shouldReceive('getSum')->andReturn('-100')->atLeast()->once();

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
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $budgetLimit  = $this->getRandomBudgetLimit();
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');


        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $repository->shouldReceive('getBudgetPeriodReport')->andReturn([])->once();
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.budget.period', [$budgetLimit->budget_id, '1', '20120101', '20120131']));
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

        // mock default session
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $repository->shouldReceive('getNoBudgetPeriodReport')->andReturn([])->once();
        $generator->shouldReceive('singleSet')->once()->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.budget.period.no-budget', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
