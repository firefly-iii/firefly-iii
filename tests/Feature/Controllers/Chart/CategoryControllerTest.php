<?php
/**
 * CategoryControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

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
        $catRepository = $this->mock(CategoryRepositoryInterface::class);

        $catRepository->shouldReceive('spentInPeriod')->andReturn('0');
        $catRepository->shouldReceive('earnedInPeriod')->andReturn('0');
        $catRepository->shouldReceive('firstUseDate')->andReturn(new Carbon);


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.all', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController::currentPeriod
     * @covers       \FireflyIII\Http\Controllers\Chart\CategoryController::makePeriodChart
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testCurrentPeriod(string $range)
    {
        // this is actually for makePeriodChart
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepository = $this->mock(CategoryRepositoryInterface::class);
        $account            = $this->user()->accounts()->where('account_type_id', 5)->first();
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        $categoryRepository->shouldReceive('spentInPeriod')->andReturn('0');
        $categoryRepository->shouldReceive('earnedInPeriod')->andReturn('0');


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.current', [1]));
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
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepository = $this->mock(CategoryRepositoryInterface::class);
        $category           = $this->user()->categories()->first();
        $account            = $this->user()->accounts()->where('account_type_id', 5)->first();
        // get one category
        $categoryRepository->shouldReceive('getCategories')->andReturn(new Collection([$category]));
        // get one account
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        // always return zero
        $categoryRepository->shouldReceive('spentInPeriod')->andReturn('0');
        $categoryRepository->shouldReceive('spentInPeriodWithoutCategory')->andReturn('0');

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
        $this->be($this->user());
        $response = $this->get(route('chart.category.period', [1, '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryController::reportPeriodNoCategory
     */
    public function testReportPeriodNoCategory()
    {
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
        $accountRepository  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepository = $this->mock(CategoryRepositoryInterface::class);
        $account            = $this->user()->accounts()->where('account_type_id', 5)->first();
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]));
        $categoryRepository->shouldReceive('spentInPeriod')->andReturn('0');
        $categoryRepository->shouldReceive('earnedInPeriod')->andReturn('0');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.category.specific', ['1', '2012-01-01']));
        $response->assertStatus(200);
    }

}
