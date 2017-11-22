<?php
/**
 * BudgetReportControllerTest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class BudgetReportControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BudgetReportControllerTest extends TestCase
{
    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController::accountExpense
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController::__construct
     */
    public function testAccountExpense()
    {
        $generator = $this->mock(GeneratorInterface::class);
        $pieChart  = $this->mock(MetaPieChartInterface::class);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setBudgets')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['expense', 'account'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.budget.account-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController::budgetExpense
     */
    public function testBudgetExpense()
    {
        $generator = $this->mock(GeneratorInterface::class);
        $pieChart  = $this->mock(MetaPieChartInterface::class);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setBudgets')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['expense', 'budget'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.budget.budget-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController::mainChart
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController::filterBudgetLimits
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController::getExpenses
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController::groupByBudget
     */
    public function testMainChart()
    {
        $generator   = $this->mock(GeneratorInterface::class);
        $collector   = $this->mock(JournalCollectorInterface::class);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);

        $one                              = factory(BudgetLimit::class)->make();
        $one->budget_id                   = 1;
        $two                              = factory(BudgetLimit::class)->make();
        $two->budget_id                   = 1;
        $two->start_date                  = new Carbon('2012-01-01');
        $two->end_date                    = new Carbon('2012-01-31');
        $transaction                      = factory(Transaction::class)->make();
        $transaction->transaction_amount  = '-100';
        $transaction->destination_amount  = '-100';
        $transaction->amount              = '-100';
        $transaction->opposing_account_id = 8;

        $budgetRepos->shouldReceive('getAllBudgetLimits')->andReturn(new Collection([$one, $two]))->once();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]])->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([TransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([OpposingAccountFilter::class])->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([PositiveAmountFilter::class])->andReturnSelf();
        $collector->shouldReceive('setBudgets')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.budget.main', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }
}
