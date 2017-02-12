<?php
/**
 * BudgetControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::amount
     */
    public function testAmount()
    {
        $data = [
            'amount' => 200,
        ];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [1]), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::create
     */
    public function testCreate()
    {
        $this->be($this->user());
        $response = $this->get(route('budgets.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('budgets.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::destroy
     */
    public function testDestroy()
    {
        $this->session(['budgets.delete.url' => 'http://localhost']);

        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('destroy')->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('budgets.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('budgets.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::index
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndex(string $range)
    {
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::noBudget
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoBudget(string $range)
    {
        $date = new Carbon();
        $this->session(['start' => $date, 'end' => clone $date]);

        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('withoutBudget')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.no-budget'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::postUpdateIncome
     */
    public function testPostUpdateIncome()
    {
        $data = [
            'amount' => '200',
        ];
        $this->be($this->user());
        $response = $this->post(route('budgets.income.post'), $data);
        $response->assertStatus(302);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::show
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range)
    {
        $date = new Carbon();
        $date->subDay();
        $this->session(['first' => $date]);

        // mock account repository
        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection);


        // mock budget repository
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('getBudgetLimits')->andReturn(new Collection);
        $budgetRepository->shouldReceive('spentInPeriod')->andReturn('1');
        // mock journal collector:
        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::showByBudgetLimit()
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByBudgetLimit(string $range)
    {
        // mock account repository
        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection);

        // mock budget repository
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('spentInPeriod')->andReturn('1');
        $budgetRepository->shouldReceive('getBudgetLimits')->andReturn(new Collection);

        // mock journal collector:
        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.show.limit', [1, 1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::store
     */
    public function testStore()
    {
        $this->session(['budgets.create.url' => 'http://localhost']);

        $data = [
            'name' => 'New Budget ' . rand(1000, 9999),
        ];
        $this->be($this->user());
        $response = $this->post(route('budgets.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::update
     */
    public function testUpdate()
    {
        $this->session(['budgets.edit.url' => 'http://localhost']);

        $data = [
            'name'   => 'Updated Budget ' . rand(1000, 9999),
            'active' => 1,
        ];
        $this->be($this->user());
        $response = $this->post(route('budgets.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::updateIncome
     */
    public function testUpdateIncome()
    {
        // must be in list
        $this->be($this->user());
        $response = $this->get(route('budgets.income', [1]));
        $response->assertStatus(200);
    }

}