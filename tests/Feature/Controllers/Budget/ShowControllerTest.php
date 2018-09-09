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

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 *
 * Class ShowControllerTest
 */
class ShowControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
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
        Log::debug(sprintf('Now in testNoBudget(%s)', $range));

        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $journalRepos->shouldReceive('firstNull')->andReturn(null);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withoutBudget')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));

        $date = new Carbon();
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.no-budget'));
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
        Log::debug(sprintf('Now in testNoBudgetAll(%s)', $range));
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->andReturn(null);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutBudget')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));

        $date = new Carbon();
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
    public function testNoBudgetDate(string $range): void
    {
        Log::debug(sprintf('Now in testNoBudgetDate(%s)', $range));
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->andReturn(null);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withoutBudget')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));

        $date = new Carbon();
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.no-budget', ['2016-01-01']));
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
        Log::debug(sprintf('Now in testShow(%s)', $range));
        // mock stuff

        $budgetLimit = factory(BudgetLimit::class)->make();

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->andReturn(new TransactionJournal);


        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();


        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);


        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');

        $date = new Carbon();
        $date->subDay();
        $this->session(['first' => $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Budget\ShowController
     */
    public function testShowByBadBudgetLimit(): void
    {
        Log::debug('Now in testShowByBadBudgetLimit()');
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('budgets.show.limit', [1, 8]));
        $response->assertStatus(500);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByBudgetLimit(string $range): void
    {
        Log::debug(sprintf('Now in testShowByBudgetLimit(%s)', $range));
        // mock stuff
        $journalRepos      = $this->mock(JournalRepositoryInterface::class);
        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository  = $this->mock(BudgetRepositoryInterface::class);
        $collector         = $this->mock(TransactionCollectorInterface::class);
        $userRepos         = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $budgetRepository->shouldReceive('spentInPeriod')->andReturn('1');
        $budgetRepository->shouldReceive('getBudgetLimits')->andReturn(new Collection);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn(new LengthAwarePaginator([], 0, 10));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.show.limit', [1, 1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}
