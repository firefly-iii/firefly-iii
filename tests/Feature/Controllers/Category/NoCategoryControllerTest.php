<?php
/**
 * NoCategoryControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Feature\Controllers\Category;


use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use Mockery;
use Navigation;
use Preferences;
use Tests\TestCase;


/**
 *
 * Class NoCategoryControllerTest
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NoCategoryControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Category\NoCategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoCategory(string $range): void
    {
        $collector = $this->mock(GroupCollectorInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $this->mockDefaultSession();
        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        $this->mockLastActivity();

        // get the journal with the most recent date for firstNull:
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withoutCategory')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([])->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([], 0, 10))->atLeast()->once();

        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Category\NoCategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testNoCategoryAll(string $range): void
    {
        $collector = $this->mock(GroupCollectorInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);

        $this->mockDefaultSession();
        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $fiscalHelper->shouldReceive('startOfFiscalYear')->andReturn(new Carbon);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->andReturn(new Carbon);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withoutCategory')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([], 0, 10))->atLeast()->once();

        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category', ['all']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Category\NoCategoryController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testNoCategoryDate(string $range): void
    {
        $collector = $this->mock(GroupCollectorInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $this->mockDefaultSession();
        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        $this->mockLastActivity();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withoutCategory')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([], 0, 10))->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([])->atLeast()->once();

        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();

        Navigation::shouldReceive('updateStartDate')->andReturn(new Carbon);
        Navigation::shouldReceive('updateEndDate')->andReturn(new Carbon);
        Navigation::shouldReceive('startOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('endOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('periodShow')->andReturn('Some date');
        Navigation::shouldReceive('blockPeriods')->andReturn([['period' => '1M', 'start' => new Carbon, 'end' => new Carbon]])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category', ['2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}
