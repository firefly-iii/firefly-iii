<?php
/**
 * CategoryReportControllerTest.php
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

namespace Tests\Feature\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use Log;
use Preferences;
use Tests\Support\TestDataTrait;
use Tests\TestCase;


/**
 * Class CategoryReportControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryReportControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testCategoryExpense(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();
        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('multiCurrencyPieChart')->andReturn([])->once();

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->categoryListExpenses());

        $this->be($this->user());
        $response = $this->get(route('chart.category.category-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testCategoryIncome(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('multiCurrencyPieChart')->andReturn([])->once();

        $opsRepos->shouldReceive('listIncome')->atLeast()->once()->andReturn($this->categoryListIncome());

        $this->be($this->user());
        $response = $this->get(route('chart.category.category-income', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testMainChart(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);
        $date         = new Carbon;
        $withdrawal   = $this->getRandomWithdrawalAsArray();

        $this->mockDefaultSession();

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->categoryListExpenses());
        $opsRepos->shouldReceive('listIncome')->atLeast()->once()->andReturn($this->categoryListIncome());

        $this->be($this->user());
        $response = $this->get(route('chart.category.main', ['1', '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
