<?php
/**
 * ShowControllerTest.php
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

namespace Tests\Feature\Controllers\Budget;

use Amount;
use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class ShowControllerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @covers       \FireflyIII\Http\Controllers\Budget\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     *
     */
    public function testNoBudget(string $range): void
    {
        $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = null;
        $this->mockDefaultSession();

        try {
            $date = new Carbon;
        } catch (Exception $e) {
            $e->getMessage();
        }

        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        // mock calls
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withoutBudget')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([], 0, 10))->atLeast()->once();
        $collector->shouldReceive('getExtractedJournals')->andReturn([])->atLeast()->once();

        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.no-budget', ['2019-01-01', '2019-01-31']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoBudgetAll(string $range): void
    {
        $this->mock(BudgetRepositoryInterface::class);
        $collector = $this->mock(GroupCollectorInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $date      = null;
        $this->mockDefaultSession();
        try {
            $date = new Carbon;
        } catch (Exception $e) {
            $e->getMessage();
        }

        // mock calls
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withoutBudget')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withAccountInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([], 0, 10))->atLeast()->once();

        try {
            $date = new Carbon;
        } catch (Exception $e) {
            $e->getMessage();
        }
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.no-budget', ['all']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range): void
    {
        $budgetLimit  = $this->getRandomBudgetLimit();
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);

        $this->mockDefaultSession();
        
        

        // mock calls
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');
        Amount::shouldReceive('formatAnything')->atLeast()->once()->andReturn('-100');

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setBudget')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([], 0, 10))->atLeast()->once();


        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);


        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');

        try {
            $date = new Carbon;
            $date->subDay();
            $this->session(['first' => $date]);
        } catch (Exception $e) {
            $e->getMessage();
        }


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByBudgetLimit(string $range): void
    {
        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository  = $this->mock(BudgetRepositoryInterface::class);
        $collector         = $this->mock(GroupCollectorInterface::class);
        $userRepos         = $this->mock(UserRepositoryInterface::class);


        $this->mockDefaultSession();


        // mock calls
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Preferences::shouldReceive('lastActivity')->atLeast()->once()->andReturn('md512345');

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $budgetRepository->shouldReceive('spentInPeriod')->andReturn('1');
        $budgetRepository->shouldReceive('getBudgetLimits')->andReturn(new Collection);

        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setPage')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('setBudget')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->atLeast()->once();
        $collector->shouldReceive('getPaginatedGroups')->andReturn(new LengthAwarePaginator([], 0, 10))->atLeast()->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.show.limit', [1, 1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}
