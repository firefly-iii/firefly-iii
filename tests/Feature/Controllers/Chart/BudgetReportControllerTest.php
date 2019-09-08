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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Tests\Support\TestDataTrait;
use Tests\TestCase;


/**
 * Class BudgetReportControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BudgetReportControllerTest extends TestCase
{
    use TestDataTrait;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController
     */
    public function testBudgetExpense(): void
    {
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->budgetListExpenses());

        // mock default session
        $this->mockDefaultSession();
        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $generator->shouldReceive('multiCurrencyPieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.budget.budget-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * TODO something in this method makes it return a 404.
     *
     * @covers       \FireflyIII\Http\Controllers\Chart\BudgetReportController
     */
    public function testMainChart(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $opsRepos = $this->mock(OperationsRepositoryInterface::class);
        $date         = new Carbon;
        $withdrawal   = $this->getRandomWithdrawalAsArray();
        $asset        = $this->getRandomAsset();
        $budget       = $this->getRandomBudget();
        $limit1       = $this->getRandomBudgetLimit();
        $limit2       = $this->getRandomBudgetLimit();

        // need to update at least one budget limit so it fits the limits that have been set below.
        $limit3             = new BudgetLimit;
        $limit3->budget_id  = $budget->id;
        $limit3->start_date = new Carbon('2012-01-01');
        $limit3->end_date   = new Carbon('2012-01-31');
        $limit3->amount     = '100';
        $limit3->save();


        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $this->mockDefaultSession();

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->budgetListExpenses());

        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.budget.main', [$asset->id, $budget->id, '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
