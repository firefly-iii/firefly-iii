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

namespace Tests\Feature\Controllers\Report;

use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Preferences;
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
        $return       = [];
        $helper       = $this->mock(BudgetReportHelperInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        //Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $helper->shouldReceive('getBudgetReport')->andReturn($return);

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
        $date         = new Carbon;

        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('x');
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
