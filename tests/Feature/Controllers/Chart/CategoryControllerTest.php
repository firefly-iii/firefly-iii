<?php
/**
 * CategoryControllerTest.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Support\Chart\Category\WholePeriodChartGenerator;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Tests\Support\TestDataTrait;
use Tests\TestCase;

/**
 * Class CategoryControllerTest
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     *
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testAll(string $range): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $chartGen     = $this->mock(WholePeriodChartGenerator::class);
        $firstUseDate = null;

        switch ($range) {
            default:
                throw new FireflyException(sprintf('No case for %s', $range));
            case '1D':
                $firstUseDate = Carbon::now()->subDays(3);
                break;
            case '1W':
                $firstUseDate = Carbon::now()->subDays(12);
                break;
            case '1M':
                $firstUseDate = Carbon::now()->subDays(40);
                break;
            case '3M':
                $firstUseDate = Carbon::now()->subDays(120);
                break;
            case '6M':
                $firstUseDate = Carbon::now()->subDays(160);
                break;
            case '1Y':
                $firstUseDate = Carbon::now()->subDays(365);
                break;
            case 'custom':
                $firstUseDate = Carbon::now()->subDays(20);
                break;
        }

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $chartGen->shouldReceive('generate')->atLeast()->once()->andReturn([]);

        $repository->shouldReceive('firstUseDate')->andReturn($firstUseDate)->once();
        $generator->shouldReceive('multiSet')->once()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.all', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontPage(string $range): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);
        $noCatRepos   = $this->mock(NoCategoryRepositoryInterface::class);
        $category     = $this->getRandomCategory();
        $account      = $this->getRandomAsset();
        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $opsRepos->shouldReceive('sumExpenses')->atLeast()->once()->andReturn($this->categorySumExpenses());
        $noCatRepos->shouldReceive('sumExpenses')->atLeast()->once()->andReturn($this->categorySumExpenses());

        $repository->shouldReceive('getCategories')->atLeast()->once()->andReturn(new Collection([$category]));
        $accountRepos->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::ASSET, AccountType::DEFAULT]])->andReturn(
            new Collection([$account])
        );
        $generator->shouldReceive('multiSet')->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.frontpage', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryController
     */
    public function testReportPeriod(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $opsRepos     = $this->mock(OperationsRepositoryInterface::class);
        $date         = new Carbon;

        $opsRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->categoryListExpenses());
        $opsRepos->shouldReceive('listIncome')->atLeast()->once()->andReturn($this->categoryListIncome());

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.period', [1, '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryController
     */
    public function testReportPeriodNoCategory(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $noCatRepos   = $this->mock(NoCategoryRepositoryInterface::class);
        $date         = new Carbon;

        $noCatRepos->shouldReceive('listExpenses')->atLeast()->once()->andReturn($this->noCategoryListExpenses());
        $noCatRepos->shouldReceive('listIncome')->atLeast()->once()->andReturn($this->noCategoryListIncome());

        $this->mockDefaultSession();
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.period.no-category', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     *
     * @throws Exception
     */
    public function testSpecificPeriod(string $range): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $chartGen     = $this->mock(WholePeriodChartGenerator::class);
        $account      = $this->getRandomAsset();
        $date         = new Carbon;

        $this->mockDefaultSession();

        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $chartGen->shouldReceive('generate')->atLeast()->once()->andReturn([]);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.specific', ['1', '2012-01-01']));
        $response->assertStatus(200);
    }
}
