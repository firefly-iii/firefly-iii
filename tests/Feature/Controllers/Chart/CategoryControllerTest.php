<?php
/**
 * CategoryControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class CategoryControllerTest
 *
 * @package Tests\Feature\Controllers\Chart
 */
class CategoryControllerTest extends TestCase
{

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController::all
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController::__construct
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testAll(string $range)
    {
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);

        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('earnedInPeriod')->andReturn('0');
        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon('1900-01-01'))->once();
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection)->once();
        $generator->shouldReceive('multiSet')->once()->andReturn([]);


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.all', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController::frontpage
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpage(string $range)
    {
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $category     = factory(Category::class)->make();
        $account      = factory(Account::class)->make();

        $repository->shouldReceive('getCategories')->andReturn(new Collection([$category]));
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('spentInPeriodWithoutCategory')->andReturn('0');
        $generator->shouldReceive('singleSet')->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.frontpage', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryController::reportPeriod
     */
    public function testReportPeriod()
    {
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);

        $repository->shouldReceive('periodExpenses')->andReturn([])->once();
        $repository->shouldReceive('periodIncome')->andReturn([])->once();
        $generator->shouldReceive('multiSet')->andReturn([])->once();


        $this->be($this->user());
        $response = $this->get(route('chart.category.period', [1, '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryController::reportPeriodNoCategory
     */
    public function testReportPeriodNoCategory()
    {
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $generator  = $this->mock(GeneratorInterface::class);

        $repository->shouldReceive('periodExpensesNoCategory')->andReturn([])->once();
        $repository->shouldReceive('periodIncomeNoCategory')->andReturn([])->once();
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.period.no-category', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController::specificPeriod
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController::makePeriodChart
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testSpecificPeriod(string $range)
    {
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $generator    = $this->mock(GeneratorInterface::class);
        $account      = factory(Account::class)->make();

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('earnedInPeriod')->andReturn('0');
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.specific', ['1', '2012-01-01']));
        $response->assertStatus(200);
    }

}
