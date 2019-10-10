<?php
/**
 * ShowControllerTest.php
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

namespace Tests\Feature\Controllers\Category;


use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Navigation;
use Preferences;
use Tests\TestCase;


/**
 *
 * Class ShowControllerTest
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShowControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Category\ShowController
     *
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testShow(string $range): void
    {
        $withdrawal    = $this->getRandomWithdrawalAsArray();
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $userRepos     = $this->mock(UserRepositoryInterface::class);
        $collector     = $this->mock(GroupCollectorInterface::class);

        $this->mockDefaultSession();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');


        // mock stuff
        $categoryRepos->shouldReceive('spentInPeriodCollection')->andReturn(new Collection);
        $categoryRepos->shouldReceive('earnedInPeriodCollection')->andReturn(new Collection);
        $categoryRepos->shouldReceive('firstUseDate')->andReturnNull();


        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->times(2);
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->atLeast()->times(2);
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([$withdrawal], 0, 10))->once();

        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([])->atLeast()->once();

        Navigation::shouldReceive('updateStartDate')->andReturn(new Carbon);
        Navigation::shouldReceive('updateEndDate')->andReturn(new Carbon);
        Navigation::shouldReceive('startOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('endOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('periodShow')->andReturn('Some date');
        Navigation::shouldReceive('blockPeriods')->andReturn([['period' => '1M', 'start' => new Carbon, 'end' => new Carbon]])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Category\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testShowAll(string $range): void
    {
        $withdrawal = $this->getRandomWithdrawalAsArray();
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $collector  = $this->mock(GroupCollectorInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        $this->mockDefaultSession();
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);


        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setCategory')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([$withdrawal], 0, 10))->once();

        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1, 'all']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }
}
