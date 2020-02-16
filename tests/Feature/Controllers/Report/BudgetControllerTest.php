<?php
/**
 * BudgetControllerTest.php
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

namespace Tests\Feature\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Amount;
use Preferences;
use Tests\Support\TestDataTrait;
use Tests\TestCase;

/**
 * Class BudgetControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BudgetControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Report\BudgetController
     */
    public function testGeneral(): void
    {
        $this->mockDefaultSession();
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $blRepos      = $this->mock(BudgetLimitRepositoryInterface::class);
        $nbRepos      = $this->mock(NoBudgetRepositoryInterface::class);
        $budget       = $this->getRandomBudget();
        $limit = $this->getRandomBudgetLimit();

        $repository->shouldReceive('getBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));
        $blRepos->shouldReceive('getBudgetLimits')->atLeast()->once()->andReturn(new Collection([$limit]));

        $opsRepos->shouldReceive('sumExpenses')->atLeast()->once()->andReturn([]);
        $nbRepos->shouldReceive('sumExpenses')->atLeast()->once()->andReturn([]);

        $date         = new Carbon;

        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $this->be($this->user());
        $response = $this->get(route('report-data.budget.general', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\BudgetController
     */
    public function testPeriod(): void
    {
        $this->mockDefaultSession();
        $first        = [1 => ['entries' => ['1', '1']]];
        $second       = ['entries' => ['1', '1']];
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $blRepos      = $this->mock(BudgetLimitRepositoryInterface::class);
        $nbRepos      = $this->mock(NoBudgetRepositoryInterface::class);
        $opsRepos = $this->mock(OperationsRepositoryInterface::class);
        $date         = new Carbon;

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->budgetListExpenses());

        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        //Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('getBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getBudgetPeriodReport')->andReturn($first);
        $repository->shouldReceive('getNoBudgetPeriodReport')->andReturn($second);

        $this->be($this->user());
        $response = $this->get(route('report-data.budget.period', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
