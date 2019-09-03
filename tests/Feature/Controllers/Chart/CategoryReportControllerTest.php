<?php
/**
 * CategoryReportControllerTest.php
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
use FireflyIII\Models\TransactionType;
use Log;
use Preferences;
use Tests\TestCase;


/**
 * Class CategoryReportControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryReportControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testAccountExpense(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();
        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.account-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testAccountIncome(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();
        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.account-income', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testCategoryExpense(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;

        $this->mockDefaultSession();
        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('pieChart')->andReturn([])->once();

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
        $date         = new Carbon;

        $this->mockDefaultSession();
        //Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('pieChart')->andReturn([])->once();

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
        $date         = new Carbon;
        $withdrawal   = $this->getRandomWithdrawalAsArray();

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $collector->shouldReceive('setAccounts')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->atLeast()->once();

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]])->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setCategories')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([$withdrawal])->atLeast()->once();
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.main', ['1', '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
