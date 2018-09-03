<?php
/**
 * IndexControllerTest.php
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
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 *
 * Class IndexControllerTest
 */
class IndexControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndex(string $range): void
    {


        Log::debug(sprintf('Now in testIndex(%s)', $range));
        // mock stuff
        $budget      = factory(Budget::class)->make();
        $budgetLimit = factory(BudgetLimit::class)->make();

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();
        $budgetInfo              = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndexOutOfRange(string $range): void
    {
        Log::debug(sprintf('Now in testIndexOutOfRange(%s)', $range));
        // mock stuff
        $budget      = factory(Budget::class)->make();
        $budgetLimit = factory(BudgetLimit::class)->make();
        $budgetInfo  = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);

        $this->be($this->user());
        $today = new Carbon;
        $today->startOfMonth();
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index', [$today->format('Y-m-d')]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndexWithDate(string $range): void
    {
        Log::debug(sprintf('Now in testIndexWithDate(%s)', $range));
        // mock stuff
        $budget      = factory(Budget::class)->make();
        $budgetLimit = factory(BudgetLimit::class)->make();
        $budgetInfo  = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index', ['2017-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndexWithInvalidDate(string $range): void
    {
        Log::debug(sprintf('Now in testIndexWithInvalidDate(%s)', $range));
        // mock stuff
        $budget      = factory(Budget::class)->make();
        $budgetLimit = factory(BudgetLimit::class)->make();

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();
        $budgetInfo              = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index', ['Hello-there']));
        $response->assertStatus(404);
    }
}
